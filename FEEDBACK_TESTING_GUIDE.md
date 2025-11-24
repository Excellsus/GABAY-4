# Feedback Archive & Delete System - Testing Guide

## 🧪 Complete Testing Checklist

Follow this guide to thoroughly test all features of the Feedback Archive & Delete system.

---

## Prerequisites

✅ Database schema updated successfully  
✅ All files uploaded to server  
✅ Browser console open (F12) for debugging  
✅ Test feedback entries exist in database

---

## Test 1: Database Schema

### Steps:
1. Open phpMyAdmin
2. Select `admin` database
3. Click on `feedback` table
4. Check "Structure" tab

### Expected Results:
- ✅ `is_archived` column exists (TINYINT)
- ✅ `deleted_at` column exists (TIMESTAMP, NULL)
- ✅ `archived_at` column exists (TIMESTAMP, NULL)

### Check Indexes:
```sql
SHOW INDEX FROM feedback;
```
- ✅ `idx_is_archived` exists
- ✅ `idx_deleted_at` exists

### Check New Table:
```sql
DESCRIBE feedback_archive_log;
```
- ✅ Table exists with correct columns

---

## Test 2: Basic Page Load

### Steps:
1. Navigate to `visitorFeedback.php`
2. Wait for page to fully load

### Expected Results:
- ✅ Page loads without errors
- ✅ Three tabs visible: Active, Archived, Trash
- ✅ "Active" tab is selected by default
- ✅ Stats cards show correct numbers
- ✅ Feedback entries display
- ✅ Checkboxes appear next to each entry
- ✅ "Select All" checkbox visible

### Browser Console:
- ✅ No JavaScript errors
- ✅ No 404 errors for CSS/JS files

---

## Test 3: Single Archive

### Steps:
1. Go to "Active" tab
2. Click "Archive" button on one feedback entry
3. Confirm the action

### Expected Results:
- ✅ Confirmation dialog appears
- ✅ Success notification displays
- ✅ Page reloads automatically
- ✅ Entry disappears from Active tab
- ✅ Go to "Archived" tab
- ✅ Entry appears in Archived tab
- ✅ "Restore" button is visible

### Database Check:
```sql
SELECT id, visitor_name, is_archived, archived_at 
FROM feedback 
WHERE is_archived = 1 
LIMIT 5;
```
- ✅ `is_archived` = 1
- ✅ `archived_at` has timestamp

### Audit Log Check:
```sql
SELECT * FROM feedback_archive_log 
ORDER BY action_at DESC LIMIT 1;
```
- ✅ Entry logged with action = 'archived'

---

## Test 4: Batch Archive

### Steps:
1. Go to "Active" tab
2. Check 3-5 feedback entries
3. Click "Archive Selected" in batch actions bar
4. Confirm the action

### Expected Results:
- ✅ Batch actions bar appears when selecting
- ✅ Selected count updates correctly (e.g., "3 selected")
- ✅ Selected items have green left border
- ✅ Confirmation shows correct count
- ✅ Success notification shows count
- ✅ All selected entries move to Archived tab

### Database Check:
```sql
SELECT COUNT(*) FROM feedback WHERE is_archived = 1;
```
- ✅ Count matches archived entries

---

## Test 5: Restore from Archive

### Steps:
1. Go to "Archived" tab
2. Click "Restore" on one entry
3. Confirm (if prompted)

### Expected Results:
- ✅ Success notification appears
- ✅ Entry disappears from Archived tab
- ✅ Entry reappears in Active tab
- ✅ "Archive" button is back

### Database Check:
```sql
SELECT id, visitor_name, is_archived 
FROM feedback 
WHERE is_archived = 0 AND deleted_at IS NULL;
```
- ✅ Restored entry has `is_archived` = 0

### Audit Log:
```sql
SELECT * FROM feedback_archive_log 
WHERE action = 'unarchived' 
ORDER BY action_at DESC LIMIT 1;
```
- ✅ Restore action logged

---

## Test 6: Soft Delete

### Steps:
1. Go to "Active" tab
2. Click "Delete" on one entry
3. Confirm the action

### Expected Results:
- ✅ Confirmation dialog: "Move this feedback entry to trash?"
- ✅ Success notification appears
- ✅ Entry disappears from Active
- ✅ Go to "Trash" tab
- ✅ Entry appears in Trash
- ✅ "Restore" and "Delete Forever" buttons visible

### Database Check:
```sql
SELECT id, visitor_name, deleted_at 
FROM feedback 
WHERE deleted_at IS NOT NULL;
```
- ✅ `deleted_at` has timestamp
- ✅ Entry not visible in Active or Archived

---

## Test 7: Restore from Trash

### Steps:
1. Go to "Trash" tab
2. Click "Restore" on one entry

### Expected Results:
- ✅ Entry moves back to Active tab
- ✅ `deleted_at` is NULL in database

### Database Check:
```sql
SELECT COUNT(*) FROM feedback 
WHERE deleted_at IS NULL AND is_archived = 0;
```
- ✅ Count increased by 1

---

## Test 8: Permanent Delete

### Steps:
1. Go to "Trash" tab
2. Select one entry
3. Click "Delete Permanently"
4. Confirm FIRST warning
5. Confirm SECOND warning

### Expected Results:
- ✅ First confirmation: "⚠️ PERMANENT DELETION" warning
- ✅ Second confirmation: "This is your final warning"
- ✅ Success notification
- ✅ Entry completely removed from Trash
- ✅ Cannot be found in any tab

### Database Check:
```sql
SELECT * FROM feedback WHERE id = [deleted_id];
```
- ✅ Returns 0 rows (entry completely gone)

### Audit Log:
```sql
SELECT * FROM feedback_archive_log 
WHERE feedback_id = [deleted_id];
```
- ✅ Log entry exists (even though feedback is gone)

---

## Test 9: Select All Functionality

### Steps:
1. Go to "Active" tab
2. Click "Select All" checkbox

### Expected Results:
- ✅ All checkboxes checked
- ✅ All items have green border
- ✅ Batch actions bar shows correct total count
- ✅ Click "Select All" again
- ✅ All checkboxes unchecked
- ✅ Batch actions bar disappears

---

## Test 10: Batch Delete

### Steps:
1. Select 5+ entries
2. Click "Delete Selected"
3. Confirm

### Expected Results:
- ✅ Confirmation shows correct count
- ✅ All entries move to Trash
- ✅ Success message shows count deleted

---

## Test 11: View Tab Switching

### Steps:
1. Click "Active" tab
2. Click "Archived" tab
3. Click "Trash" tab
4. Click "Active" tab again

### Expected Results:
- ✅ Each tab shows correct entries
- ✅ Active tab highlighted in green
- ✅ URL updates with `?view=` parameter
- ✅ Stats cards remain accurate
- ✅ Filters preserved when switching

---

## Test 12: Filter Integration

### Steps:
1. Go to "Active" tab
2. Set filter: "5 Stars" rating
3. Select a filtered entry
4. Archive it
5. Switch to "Archived" tab

### Expected Results:
- ✅ Filters work in all tabs
- ✅ Archived entry appears
- ✅ Filter settings preserved
- ✅ Stats cards update correctly

---

## Test 13: Empty States

### Steps:
1. Archive ALL active feedback
2. View "Active" tab
3. Delete all archived feedback
4. View "Archived" tab
5. Restore all from trash
6. View "Trash" tab

### Expected Results:
- ✅ Active empty state: "No Feedback Found"
- ✅ Archived empty state: "No Archived Feedback"
- ✅ Trash empty state: "Trash is Empty"
- ✅ Each shows appropriate icon and message

---

## Test 14: Keyboard Shortcuts

### Steps:
1. Press `Ctrl + A` (Cmd + A on Mac)
2. Press `Escape`

### Expected Results:
- ✅ `Ctrl + A` selects all entries
- ✅ `Escape` cancels selection
- ✅ Batch actions bar appears/disappears accordingly

---

## Test 15: Mobile Responsiveness

### Steps:
1. Open browser DevTools (F12)
2. Toggle device toolbar (Ctrl + Shift + M)
3. Select "iPhone 12 Pro" or similar
4. Test all features

### Expected Results:
- ✅ Tabs scroll horizontally if needed
- ✅ Batch actions stack vertically
- ✅ Buttons are touch-friendly (min 44x44px)
- ✅ Text readable without zooming
- ✅ All features work on touch
- ✅ No horizontal scrolling

### Test Different Sizes:
- ✅ 320px width (iPhone SE)
- ✅ 768px width (iPad)
- ✅ 1024px+ (Desktop)

---

## Test 16: Error Handling

### Test Invalid Action:
1. Open browser console
2. Run:
```javascript
callFeedbackAPI('invalid_action', [1]);
```

**Expected**: Error notification displayed

### Test Network Error:
1. Rename `feedback_management_api.php` temporarily
2. Try to archive an entry

**Expected**: 
- ✅ Error notification: "Connection error"
- ✅ No page crash

### Test Empty Selection:
1. Don't select any entries
2. Click "Archive Selected"

**Expected**: 
- ✅ Error notification: "Please select at least one feedback entry"

---

## Test 17: Concurrent Operations

### Steps:
1. Select 3 entries
2. Click "Archive Selected"
3. Immediately click "Delete Selected" (before reload)

### Expected Results:
- ✅ Only first action completes
- ✅ No database corruption
- ✅ Transaction integrity maintained

---

## Test 18: Large Batch Operations

### Steps:
1. Create 50+ test feedback entries
2. Select all 50+
3. Click "Archive Selected"
4. Confirm

### Expected Results:
- ✅ All entries archived successfully
- ✅ No timeout errors
- ✅ Database handles batch insert
- ✅ Reasonable response time (< 3 seconds)

---

## Test 19: Audit Trail Accuracy

### Steps:
1. Archive one entry (ID: 10)
2. Restore it
3. Delete it
4. Restore it again
5. Delete it permanently

### Check Audit Log:
```sql
SELECT * FROM feedback_archive_log 
WHERE feedback_id = 10 
ORDER BY action_at ASC;
```

**Expected**: 5 entries in order:
1. ✅ archived
2. ✅ unarchived
3. ✅ deleted
4. ✅ restored
5. ✅ deleted (permanent)

---

## Test 20: Statistics Accuracy

### Steps:
1. Note current stats (total, average, highest)
2. Archive 3 entries
3. Check stats
4. Delete 2 entries
5. Check stats again

### Expected Results:
- ✅ Total decreases as entries move out of Active
- ✅ Average rating recalculates correctly
- ✅ Highest rating updates if needed
- ✅ Numbers always accurate

### Verify with SQL:
```sql
-- Active count
SELECT COUNT(*) FROM feedback 
WHERE is_archived = 0 AND deleted_at IS NULL;

-- Average rating (active only)
SELECT AVG(rating) FROM feedback 
WHERE is_archived = 0 AND deleted_at IS NULL;
```

---

## Test 21: Security Tests

### SQL Injection Test:
```javascript
// Try malicious input
callFeedbackAPI('archive', ["1'; DROP TABLE feedback; --"]);
```

**Expected**: 
- ✅ Request fails gracefully
- ✅ No database damage
- ✅ Error logged

### XSS Test:
1. Create feedback with comment: `<script>alert('XSS')</script>`
2. View in feedback list

**Expected**:
- ✅ Script not executed
- ✅ HTML entities escaped
- ✅ Displays as text

---

## Test 22: Browser Compatibility

Test in multiple browsers:

### Chrome/Edge:
- ✅ All features work
- ✅ Animations smooth
- ✅ No console errors

### Firefox:
- ✅ All features work
- ✅ Checkboxes styled correctly
- ✅ Notifications display

### Safari:
- ✅ All features work
- ✅ CSS animations work
- ✅ Touch events work on iOS

---

## Test 23: Performance Test

### Steps:
1. Open browser Performance tab
2. Record while archiving 10 entries
3. Stop recording

### Expected Results:
- ✅ No memory leaks
- ✅ No layout thrashing
- ✅ Smooth 60fps animations
- ✅ API response < 500ms
- ✅ Page reload < 2 seconds

---

## Test 24: Data Integrity

### Test Archiving Archived Entry:
```sql
-- Manually set entry as archived
UPDATE feedback SET is_archived = 1 WHERE id = 5;
```
1. Try to archive entry #5 again via UI

**Expected**: 
- ✅ No error
- ✅ Entry stays archived
- ✅ Only one audit log entry created

### Test Deleting Deleted Entry:
```sql
UPDATE feedback SET deleted_at = NOW() WHERE id = 6;
```
1. Try to delete entry #6 via UI

**Expected**:
- ✅ Entry already in Trash
- ✅ No duplicate in trash
- ✅ Integrity maintained

---

## Test 25: Edge Cases

### Test Empty Database:
1. Delete ALL feedback entries
2. View page

**Expected**:
- ✅ No errors
- ✅ Stats show 0
- ✅ Empty state displays
- ✅ No UI breaks

### Test Single Entry:
1. Have only 1 feedback entry
2. Archive it

**Expected**:
- ✅ Works normally
- ✅ Stats update to 0
- ✅ Empty state shows

### Test Long Feedback Text:
1. Feedback with 10,000 character comment
2. Archive it

**Expected**:
- ✅ Displays correctly
- ✅ Archives successfully
- ✅ No UI breaking

---

## 📊 Test Results Template

Use this template to track your testing:

```
Test Date: ___________
Tester: ___________
Environment: [ ] Local [ ] Production

| Test # | Test Name | Status | Notes |
|--------|-----------|--------|-------|
| 1 | Database Schema | ✅ PASS | |
| 2 | Basic Page Load | ✅ PASS | |
| 3 | Single Archive | ✅ PASS | |
| ... | ... | ... | |

Overall Status: [ ] PASS [ ] FAIL
Bugs Found: ___________
```

---

## 🐛 Common Issues & Solutions

### Issue: Checkboxes not appearing
**Solution**: Clear browser cache, verify CSS loaded

### Issue: Actions not working
**Solution**: Check browser console, verify API file exists

### Issue: Database errors
**Solution**: Verify schema updated, check PDO connection

### Issue: Slow performance
**Solution**: Check database indexes, verify batch size < 100

---

## ✅ Sign-Off Checklist

Before marking as production-ready:

- [ ] All 25 tests passed
- [ ] No console errors
- [ ] Mobile responsive verified
- [ ] Browser compatibility checked
- [ ] Security tests passed
- [ ] Performance acceptable
- [ ] Database integrity verified
- [ ] Audit trail working
- [ ] Documentation complete
- [ ] User manual created

---

## 📞 Report Issues

If any test fails:
1. Document the exact steps
2. Capture screenshot/console log
3. Note browser and OS version
4. Check error logs (PHP and JavaScript)
5. Review relevant documentation

---

**Testing Time Estimate**: 2-3 hours for complete suite

**Recommended**: Run this test suite after any code changes or updates.
