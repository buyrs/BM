# Bail Mobilité Design System

## Brand Identity
- **Company Name**: Bail Mobilité
- **Industry**: Property Management / Mobility Services
- **Design Philosophy**: Clean, professional, accessible dashboard interface

## Color Palette

### Primary Colors
```css
--primary-color: #137fec;     /* Main brand blue - buttons, active states */
--accent-color: #0a5bb5;      /* Darker blue - hover states, focus rings */
--secondary-color: #e0efff;   /* Light blue - backgrounds, inactive states */
```

### Background Colors
```css
--background-color: #f8faff;  /* Main page background - light blue tint */
--white: #ffffff;             /* Cards, sidebar, header backgrounds */
```

### Text Colors
```css
--text-primary: #1a202c;      /* Main text - dark gray/black */
--text-secondary: #5c6b8a;    /* Secondary text - medium gray */
```

### Status Colors
```css
/* Success States */
--success-bg: #f0fdf4;        /* Light green background */
--success-text: #166534;      /* Dark green text */
--success-border: #22c55e;    /* Green border */

/* Warning States */
--warning-bg: #fefce8;        /* Light yellow background */
--warning-text: #a16207;      /* Dark yellow text */
--warning-border: #eab308;    /* Yellow border */

/* Error States */
--error-bg: #fef2f2;          /* Light red background */
--error-text: #dc2626;        /* Dark red text */
--error-border: #ef4444;      /* Red border */

/* Info States */
--info-bg: #eff6ff;           /* Light blue background */
--info-text: #1d4ed8;         /* Dark blue text */
--info-border: #3b82f6;       /* Blue border */
```

### Alert Priority Colors
```css
/* Critical/High Priority */
--critical-bg: #fef2f2;       /* Light red */
--critical-text: #dc2626;     /* Red text */
--critical-border: #ef4444;   /* Red border */

/* Medium Priority */
--medium-bg: #fef3c7;         /* Light orange */
--medium-text: #d97706;       /* Orange text */
--medium-border: #f59e0b;     /* Orange border */

/* Low Priority */
--low-bg: #fefce8;            /* Light yellow */
--low-text: #a16207;          /* Yellow text */
--low-border: #eab308;        /* Yellow border */
```

## Typography

### Font Family
```css
font-family: 'Inter', sans-serif;
```

### Font Weights
- **400**: Regular text
- **500**: Medium weight (navigation items)
- **600**: Semi-bold (labels, secondary headings)
- **700**: Bold (primary headings)
- **800**: Extra bold (large numbers, statistics)

### Font Sizes
```css
/* Headings */
--text-3xl: 1.875rem;         /* 30px - Main page titles */
--text-xl: 1.25rem;           /* 20px - Section headings */
--text-lg: 1.125rem;          /* 18px - Card titles */
--text-4xl: 2.25rem;          /* 36px - Large statistics */

/* Body Text */
--text-sm: 0.875rem;          /* 14px - Small text, labels */
--text-xs: 0.75rem;           /* 12px - Very small text, captions */
```

## Spacing System

### Base Spacing Unit
```css
--spacing-unit: 0.25rem;      /* 4px base unit */
```

### Spacing Scale
```css
--space-1: 0.25rem;           /* 4px */
--space-2: 0.5rem;            /* 8px */
--space-3: 0.75rem;           /* 12px */
--space-4: 1rem;              /* 16px */
--space-6: 1.5rem;            /* 24px */
--space-8: 2rem;              /* 32px */
--space-12: 3rem;             /* 48px */
```

### Component Spacing
```css
/* Padding */
--padding-sm: 0.75rem;        /* 12px - Small components */
--padding-md: 1rem;           /* 16px - Medium components */
--padding-lg: 1.5rem;         /* 24px - Large components */
--padding-xl: 2rem;           /* 32px - Extra large components */

/* Margins */
--margin-sm: 0.75rem;         /* 12px */
--margin-md: 1rem;            /* 16px */
--margin-lg: 1.5rem;          /* 24px */
--margin-xl: 2rem;            /* 32px */
```

## Border Radius
```css
--radius-sm: 0.375rem;        /* 6px - Small elements */
--radius-md: 0.5rem;          /* 8px - Buttons, inputs */
--radius-lg: 0.75rem;         /* 12px - Cards, containers */
--radius-xl: 1rem;            /* 16px - Large containers */
--radius-full: 9999px;        /* Fully rounded - avatars, pills */
```

## Shadows
```css
--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
--shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
--shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
--shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
```

## Component Guidelines

### Buttons
```css
/* Primary Button */
background: var(--primary-color);
color: white;
padding: 0.75rem 1.5rem;
border-radius: var(--radius-md);
font-weight: 500;
transition: all 200ms ease;

/* Hover State */
background: var(--accent-color);

/* Focus State */
outline: 2px solid var(--primary-color);
outline-offset: 2px;
```

### Cards
```css
background: white;
border-radius: var(--radius-xl);
box-shadow: var(--shadow-md);
padding: var(--padding-lg);
border: 1px solid rgba(0, 0, 0, 0.05);
```

### Navigation
```css
/* Active State */
background: var(--secondary-color);
color: var(--primary-color);
font-weight: 500;

/* Inactive State */
color: var(--text-secondary);
transition: all 200ms ease;

/* Hover State */
background: var(--secondary-color);
color: var(--primary-color);
```

### Status Badges
```css
/* Success */
background: var(--success-bg);
color: var(--success-text);
border: 1px solid var(--success-border);

/* Warning */
background: var(--warning-bg);
color: var(--warning-text);
border: 1px solid var(--warning-border);

/* Error */
background: var(--error-bg);
color: var(--error-text);
border: 1px solid var(--error-border);
```

## Layout System

### Grid System
```css
/* Dashboard Grid */
grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
gap: 1.5rem;

/* Statistics Grid */
grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
gap: 1.5rem;

/* Alert Grid */
grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
gap: 1.5rem;
```

### Breakpoints
```css
/* Mobile First Approach */
--mobile: 640px;              /* sm */
--tablet: 768px;              /* md */
--desktop: 1024px;            /* lg */
--wide: 1280px;               /* xl */
```

## Icons
- **Icon Library**: Material Symbols Outlined
- **Icon Size**: 24px (text-2xl) for navigation, 20px for inline icons
- **Icon Color**: Inherits from parent text color

## Accessibility Guidelines

### Color Contrast
- All text meets WCAG AA standards (4.5:1 ratio minimum)
- Interactive elements have clear focus states
- Status colors are distinguishable for colorblind users

### Interactive States
```css
/* Focus Ring */
focus:outline-none;
focus:ring-2;
focus:ring-[var(--primary-color)];
focus:ring-opacity-50;

/* Hover Transitions */
transition: all 200ms ease;
```

## Usage Examples

### Creating a Primary Button
```html
<button class="bg-[var(--primary-color)] text-white px-6 py-3 rounded-md hover:bg-[var(--accent-color)] focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:ring-opacity-50 transition-colors duration-200">
  Button Text
</button>
```

### Creating a Status Card
```html
<div class="bg-white rounded-xl shadow p-6 border-l-4 border-[var(--primary-color)]">
  <h3 class="text-lg font-semibold text-[var(--text-secondary)]">Card Title</h3>
  <p class="text-4xl font-extrabold text-[var(--text-primary)] mt-2">Value</p>
</div>
```

### Creating an Alert Section
```html
<div class="bg-white rounded-xl shadow p-6 mb-8 border border-red-200">
  <h3 class="text-xl font-bold text-red-700 flex items-center mb-4">
    <span class="material-symbols-outlined mr-2">priority_high</span>
    Alert Title
  </h3>
  <!-- Alert content -->
</div>
```

## Implementation Notes

1. **CSS Variables**: All colors and spacing use CSS custom properties for easy theming
2. **Tailwind Integration**: Design tokens work seamlessly with Tailwind CSS
3. **Responsive Design**: Mobile-first approach with consistent breakpoints
4. **Performance**: Optimized for fast loading with minimal custom CSS
5. **Maintainability**: Centralized design tokens make updates easy

## File Structure
```
palette.md          # This design system documentation
styles/
  ├── variables.css # CSS custom properties
  ├── components.css # Component-specific styles
  └── utilities.css # Utility classes
```

This design system provides a comprehensive foundation for building consistent, accessible, and maintainable interfaces for the Bail Mobilité platform.
