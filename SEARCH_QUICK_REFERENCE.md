# Search Feature - Quick Reference

## Visual Appearance

```
┌─────────────────────────────────────────────────────────────────┐
│ Header                                                          │
└─────────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│  [1F][2F][3F]  🔍 [Search rooms or services...        ] [×]   │
│                     ▼                                           │
│                ┌──────────────────────────────────────────┐     │
│                │ 🚪 Office of the Mayor        [Floor 2] │     │
│                │    Main administrative office           │     │
│                │ 🔔 Building Permits Office    [Floor 1] │     │
│                │    Services: Building permits, ...      │     │
│                └──────────────────────────────────────────┘     │
│                                                                 │
│  [Map View]                                                    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Search States

### 1. Empty State (Default)
```
🔍 [Search rooms or services...        ]
```
- Clear button hidden
- Results dropdown hidden
- Placeholder text visible

### 2. Typing State
```
🔍 [admin                               ] [×]
```
- Clear button visible
- 300ms debounce active
- Results dropdown preparing

### 3. Results State
```
🔍 [admin                               ] [×]
    ┌────────────────────────────────────────┐
    │ 🚪 Administration Office    [Floor 2] │
    │    Main administrative office          │
    │ 🚪 Admin Services Office    [Floor 1] │
    │    Services: Administrative support    │
    └────────────────────────────────────────┘
```
- Results visible with matches
- Keywords highlighted in yellow
- Hover effects active

### 4. No Results State
```
🔍 [xyz123                              ] [×]
    ┌────────────────────────────────────────┐
    │         🔍                             │
    │  No results found for "xyz123"         │
    │  Try different keywords                │
    └────────────────────────────────────────┘
```
- Empty results message
- Search icon displayed
- Helpful hint provided

## Keyword Highlighting

### Example 1: Single Keyword
**Search:** "admin"  
**Result:** `<Admin>istration Office`  
**Display:** **Admin**istration Office (yellow highlight)

### Example 2: Multiple Keywords
**Search:** "office mayor"  
**Result:** `<Office> of the <Mayor>`  
**Display:** **Office** of the **Mayor** (both highlighted)

### Example 3: Service Match
**Search:** "permit"  
**Result:** `Building Permits Office`  
           `Services: Building <permit>s, Renovation approvals`  
**Display:** Building Permits Office (yellow bell icon)  
           Services: Building **permit**s, Renovation approvals

## Color Scheme

### Icons
- **Room Icon (🚪):** Blue background (#dbeafe), Blue icon (#2563eb)
- **Service Icon (🔔):** Yellow background (#fef3c7), Yellow icon (#d97706)

### Highlights
- **Keyword Background:** Yellow (#fef08a)
- **Keyword Text:** Dark yellow (#854d0e)
- **Keyword Font:** Semi-bold (600)

### Results
- **Default Background:** White
- **Hover Background:** Light gray (#f8fafc)
- **Border on Hover:** Gray (#e2e8f0)

### Input States
- **Default Shadow:** `0 2px 8px rgba(0, 0, 0, 0.15)`
- **Focus Shadow:** `0 4px 12px rgba(4, 170, 109, 0.3)` (green accent)

## Responsive Behavior

### Desktop (>768px)
```
[1F][2F][3F]  🔍 [Search rooms...    ] [×]
```
- Side-by-side layout
- Search max-width: 400px
- Results max-height: 400px

### Tablet (≤768px)
```
[1F][2F][3F]
🔍 [Search rooms...             ] [×]
```
- Vertical stacking
- Full width search
- Results max-height: 300px

### Mobile (≤480px)
```
[1F][2F][3F]
🔍 [Search...  ] [×]
```
- Compact layout
- Reduced font sizes
- Optimized touch targets

## User Flow

### Flow 1: Direct Search
```
1. User types "mayor"
   ↓
2. After 300ms, search executes
   ↓
3. Results show: "Office of the Mayor"
   ↓
4. User clicks result
   ↓
5. Floor switches to Floor 2 (if needed)
   ↓
6. Office highlighted with "YOU ARE HERE"
   ↓
7. Details drawer opens
   ↓
8. Map pans to office location
   ↓
9. Search clears automatically
```

### Flow 2: Service Search
```
1. User types "permit"
   ↓
2. Results show multiple offices with permit services
   ↓
3. User sees both room name and services
   ↓
4. User selects "Building Permits Office"
   ↓
5. Navigation proceeds as above
```

### Flow 3: Clear Search
```
1. User types something
   ↓
2. Clear button (×) appears
   ↓
3. User clicks ×
   ↓
4. Input clears
   ↓
5. Results close
   ↓
6. Focus returns to input
```

## Integration Points

```
┌─────────────────────────────────────┐
│         Search Feature              │
└─────────────────────────────────────┘
           │
           ├──► officesData (PHP array)
           ├──► getFloorFromLocation()
           ├──► switchToFloor()
           ├──► window.showYouAreHere()
           ├──► handleRoomClick()
           └──► window.svgPanZoomInstance
```

## API Reference

### Key Functions

```javascript
// Highlight keywords in text
highlightKeywords(text, keywords)
  → Returns HTML string with <span class="highlight">

// Execute search query
performSearch(query)
  → Searches officesData
  → Renders results
  → Attaches click handlers

// Search input handler (with debounce)
searchInput.addEventListener('input', ...)
  → 300ms delay
  → Calls performSearch()

// Result click handler
searchResultItem.addEventListener('click', ...)
  → Switches floor if needed
  → Highlights office
  → Opens drawer
  → Pans to location
```

### Data Structure

```javascript
// Office object structure
{
  id: 123,
  name: "Office Name",
  details: "Description",
  services: "Available services",
  contact: "Contact info",
  location: "room-205-2"  // Format: room-{num}-{floor}
}

// Search result structure
{
  type: "room" | "service",
  office: {...},
  floor: 2,
  matchType: "name" | "service" | "details" | "contact"
}
```

## Debug Commands

```javascript
// Check search initialization
console.log('Search input:', document.getElementById('office-search'));

// Test highlight function
console.log(highlightKeywords('Administration Office', 'admin'));

// Check offices data
console.log('Offices:', officesData);

// Test floor detection
console.log('Floor:', getFloorFromLocation('room-205-2'));

// Monitor search performance
console.time('search');
performSearch('admin');
console.timeEnd('search');
```

## Common Issues

### Issue: Search not working
**Check:**
1. Console errors?
2. `officesData` defined?
3. Search input element exists?

### Issue: No highlighting
**Check:**
1. `.highlight` CSS class exists?
2. Keywords contain special characters?
3. HTML escaping issues?

### Issue: Floor not switching
**Check:**
1. Office has valid `location` field?
2. `getFloorFromLocation()` returns floor number?
3. `switchToFloor()` function available?

### Issue: Results not closing
**Check:**
1. Click outside event listener attached?
2. Z-index conflicts?
3. Console shows initialization message?

---

**Quick Tips:**
- Press Enter to select first result
- Click × to clear search quickly
- Search works on name, details, services, and contact
- Results auto-switch floors when needed
- Keyword highlighting is case-insensitive
