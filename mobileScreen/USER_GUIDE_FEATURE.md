# Interactive Mobile User Guide

## Overview

The GABAY mobile interface features a modern, interactive visual tour system that provides hands-on guidance for new visitors through actual UI element highlighting and step-by-step demonstrations.

## Features

### 🎯 Automatic First-Time Detection

- Detects when a user visits the mobile interface for the first time
- Uses localStorage to track visitor status
- Shows interactive tour automatically after 1.5 seconds on first visit

### 🎮 Interactive Visual Tour

- **Step-by-step visual guidance** with spotlight highlighting
- **Real UI element targeting** - highlights actual buttons and areas
- **Animated demonstrations** showing how to interact with each feature
- **8 Interactive Steps:**
  1. **Welcome** - Introduction with animated greeting
  2. **Pan & Zoom** - Visual pinch-zoom demonstration on floor plan
  3. **Floor Switching** - Highlights floor selector buttons
  4. **Room Selection** - Shows how to click on office rooms
  5. **Drawer Interaction** - Demonstrates dragging the details drawer
  6. **Panorama Views** - Highlights camera icons for 360° views
  7. **Office Directory** - Shows rooms list button
  8. **Completion** - Success confirmation with help button reference

### 🎨 Modern Visual Interface

- **Spotlight System** - Dynamic highlighting of UI elements with glowing effects
- **Animated Tooltips** - Smooth slide-in tooltips with contextual information
- **Interactive Animations** - Hand gestures, pinch-zoom demos, tap animations
- **Step Progress Indicator** - Shows current step and total progress
- **Touch-Friendly Controls** - Large buttons optimized for mobile interaction
- **Dark Mode Support** - Full dark theme compatibility
- **Responsive Design** - Adapts to all mobile screen sizes

### 💾 Smart Persistence

- **30-Day Cycle** - Guide reshows after 30 days for returning users
- **User Preferences** - Respects "Don't Show Again" choice
- **Manual Access** - Always available via help button in header
- **Clean Manual Access** - Shows standard guide when manually opened

## Technical Implementation

### localStorage Keys

- `gabay_has_visited` - Tracks if user has visited before
- `gabay_last_guide_shown` - Timestamp of last guide display
- `gabay_guide_disabled` - User's preference to disable auto-show

### Functions

- `initUserGuide()` - Initializes the guide system and creates tour overlay
- `checkAndShowFirstTimeGuide()` - Handles first-time detection
- `startInteractiveTour()` - Launches the interactive visual tour
- `showTourStep(stepIndex)` - Displays specific tour step with spotlight
- `nextTourStep()` - Advances to next step in the tour
- `endInteractiveTour()` - Completes tour and cleanup
- `positionTourElements(step)` - Positions spotlight and tooltip
- `getTourAnimation(type)` - Returns appropriate animation for step
- `setupTourInteraction(step)` - Adds visual emphasis to target elements

### CSS Classes

- `.interactive-tour-overlay` - Main tour overlay container
- `.tour-backdrop` - Semi-transparent background with blur effect
- `.tour-spotlight` - Dynamic spotlight highlighting target elements
- `.tour-tooltip` - Floating tooltip with step information
- `.tour-highlight` - Pulsing highlight effect for interactive elements
- `.animated-*` - Various animation classes (wave, tap, drag, etc.)
- `.first-time-pulse` - Animated help button for new users

## Testing Functions

Available in browser console for development:

```javascript
// Reset user guide status (makes user appear as first-time visitor)
window.resetUserGuideStatus();

// Start interactive tour immediately
window.testInteractiveTour();

// Jump to specific tour step (0-7)
window.showTourStep(3); // Shows step 4 (room selection)

// Add pulse animation to help button
window.testHelpButtonPulse();
```

## File Location

The user guide functionality is integrated into:

- `mobileScreen/explore.php` - Main implementation

## User Experience Flow

### First-Time Visitor

1. User opens mobile interface
2. Page loads and detects first-time visitor
3. After 1.5 seconds, welcome guide appears automatically
4. User can:
   - Skip guide (will show again in 30+ days)
   - Don't show again (disables for 1 year)
   - Got it! (normal completion, shows again in 30+ days)
5. Help button shows pulse animation and "NEW" badge

### Returning Visitor (Within 30 Days)

- No automatic guide display
- Help button available for manual access
- Shows clean guide without welcome message

### Returning Visitor (After 30+ Days)

- Automatic guide display returns
- Treated as refresher for returning users

## Benefits

### For New Visitors

- Immediate orientation and guidance
- Reduces learning curve
- Increases confidence in using the system
- Highlights key features they might miss

### For Returning Visitors

- Non-intrusive for frequent users
- Refresher available when needed
- Manual access always available

### For Administrators

- Reduces support requests
- Improves user adoption
- Analytics potential (can track guide completion rates)

## Accessibility Features

- High contrast colors
- Clear typography
- Touch-friendly button sizes
- Keyboard navigation support (ESC to close)
- Screen reader friendly structure

## Mobile Responsiveness

- Full-screen modal on mobile devices
- Touch-optimized button sizes
- Simplified layout for small screens
- Vertical button stack on mobile
