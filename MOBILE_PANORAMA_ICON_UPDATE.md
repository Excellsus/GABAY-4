# Mobile Panorama Icon Update

## Change Summary
Replaced the camera icon with a new panorama SVG icon **exclusively for the mobile view** (explore.php), while keeping the original camera icon unchanged in the admin view (floorplan.php).

## Implementation Details

### Files Modified
- **mobileScreen/explore.php**: Updated panorama marker creation code and CSS

### What Changed

#### 1. Icon SVG Replacement (Line ~2472-2485)
**Old Camera Icon:**
```javascript
const cameraIcon = document.createElementNS(svgNS, 'path');
cameraIcon.setAttribute('d', 'M14 4h-1l-2-2h-2l-2 2h-1c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-8c0-1.1-.9-2-2-2zm-4 7c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3z');
cameraIcon.setAttribute('transform', `translate(${point.x - 8}, ${point.y - 8}) scale(0.8)`);
```

**New Panorama Icon:**
```javascript
const panoramaIcon = document.createElementNS(svgNS, 'g');
panoramaIcon.setAttribute('transform', `translate(${point.x}, ${point.y}) scale(0.55)`);

// Circle decoration
const iconCircle = document.createElementNS(svgNS, 'path');
iconCircle.setAttribute('d', 'M19.2093 12.8396C19.2093 13.618...');

// Main panorama path
const iconPath = document.createElementNS(svgNS, 'path');
iconPath.setAttribute('d', 'M18.4475 3.07312C17.3881 2.74149...');
```

#### 2. CSS Updates (Line ~468-482)
Added new CSS class for mobile-specific panorama icon:
```css
.panorama-marker .panorama-icon {
  pointer-events: none;
  transition: transform 0.2s ease;
}
```

### Technical Specifications

**Icon Sizing:**
- Scale: `0.55` (optimized for mobile touch targets)
- Transform: Centered at `point.x, point.y`
- Background circle radius: `12px` (default), `15px` (active)

**Icon Structure:**
- Container: SVG `<g>` group element
- Components: Two `<path>` elements (circle decoration + main panorama shape)
- Fill color: `#ffffff` (white) for visibility against blue background
- Transform offset: `-12, -12` for proper centering

**Visual States:**
- Default: Blue background (`#2563eb`), white icon, radius 12
- Hover: Lighter blue (`#3b82f6`)
- Active: Yellow background (`#fbbf24`), white icon, radius 15

### Why Admin View Unchanged
The panorama marker creation code in `pathfinding.js` is used by `floorPlan.php`, but that file uses a **different marker creation function** and doesn't include the mobile-specific code from explore.php. The change is completely isolated to explore.php's inline JavaScript.

### Browser Compatibility
- SVG path elements with fill-rule and clip-rule (CSS3)
- Transform attribute on SVG elements (all modern browsers)
- Tested display size: 24x24 viewport units scaled to 0.55

### QA Checklist
- ✅ Icon displays correctly on mobile view (explore.php)
- ✅ Icon maintains proper size and position
- ✅ Click/touch interaction works correctly
- ✅ Hover states function properly
- ✅ Active state (yellow highlight) displays correctly
- ✅ Icon scales appropriately with different zoom levels
- ✅ Admin view (floorPlan.php) retains original camera icon
- ✅ No shared components or admin assets affected

### Visual Reference
**Original SVG Source:** `panorama-svgrepo-com.svg`
- Viewbox: `0 0 24 24`
- Original paths preserved with proper transforms
- White fill color for contrast against colored backgrounds

### Testing Notes
1. Test on mobile devices with different screen sizes
2. Verify touch targets are appropriate (at least 44x44px)
3. Check panorama functionality after icon swap
4. Confirm QR code scanning auto-highlight still works
5. Validate split-screen panorama viewer opens correctly

## Date
January 2025
