---
name: LanceDesk Form Guard
colors:
  surface: '#f5faff'
  surface-dim: '#d5dbe0'
  surface-bright: '#f5faff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4fa'
  surface-container: '#e9eef4'
  surface-container-high: '#e3e9ee'
  surface-container-highest: '#dde3e8'
  on-surface: '#161c20'
  on-surface-variant: '#414750'
  inverse-surface: '#2b3135'
  inverse-on-surface: '#ecf1f7'
  outline: '#717881'
  outline-variant: '#c1c7d2'
  surface-tint: '#0062a1'
  primary: '#005891'
  on-primary: '#ffffff'
  primary-container: '#2271b1'
  on-primary-container: '#e9f1ff'
  inverse-primary: '#9ccaff'
  secondary: '#1a629a'
  on-secondary: '#ffffff'
  secondary-container: '#85c0fe'
  on-secondary-container: '#004e81'
  tertiary: '#7d4900'
  on-tertiary: '#ffffff'
  tertiary-container: '#9f5e00'
  on-tertiary-container: '#ffeee0'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d0e4ff'
  primary-fixed-dim: '#9ccaff'
  on-primary-fixed: '#001d35'
  on-primary-fixed-variant: '#00497a'
  secondary-fixed: '#d0e4ff'
  secondary-fixed-dim: '#9bcaff'
  on-secondary-fixed: '#001d35'
  on-secondary-fixed-variant: '#004a7a'
  tertiary-fixed: '#ffdcbd'
  tertiary-fixed-dim: '#ffb86e'
  on-tertiary-fixed: '#2c1600'
  on-tertiary-fixed-variant: '#693c00'
  background: '#f5faff'
  on-background: '#161c20'
  surface-variant: '#dde3e8'
typography:
  hero-title:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 23px
    fontWeight: '600'
    lineHeight: 32px
  headline-md:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  unit: 4px
  container-padding: 20px
  gutter: 16px
  margin-sm: 8px
  margin-md: 16px
  margin-lg: 24px
---

## Brand & Style

The design system is a utility-focused, professional extension of the native WordPress administration environment. It prioritizes clarity, reliability, and seamless integration for a security-centric tool. The aesthetic is rooted in **Corporate / Modern** principles, utilizing the established visual language of WordPress to ensure user familiarity and trust.

The interface is characterized by high legibility, structured information density, and a calm, functional atmosphere. By adhering to the native "wp-admin" aesthetic, the system minimizes cognitive load for administrators, positioning the tool as an essential, integrated part of the core site management experience.

## Colors

The palette is strictly aligned with the WordPress core administrative color scheme. The primary color is a dependable blue used for critical actions and navigational cues.

- **Primary Action**: Use `#2271B1` for buttons and links, transitioning to `#135E96` on hover.
- **Surface & Chrome**: The page background uses `#F0F0F1` to provide a soft contrast against `#FFFFFF` content panels.
- **Typography**: Primary content is set in `#1D2327` for maximum contrast, while metadata and descriptions use `#646970`.
- **Feedback**: Success, Warning, and Danger colors are used sparingly for status indicators, notices, and destructive actions to maintain a calm baseline.

## Typography

This design system utilizes a hybrid approach. For hero titles and primary branding moments, a clean geometric sans-serif provides a modern, distinctive touch. For all functional UI, tables, and settings, the system defaults to a systematic, humanist sans-serif stack to match the native environment.

- **Hero Titles**: Reserved for the main dashboard header or feature introductions.
- **Headlines**: Used for section titles within panels.
- **Body Text**: Standard 13px or 14px sizes are used for most descriptive text to match the density of the WordPress admin.
- **Monospace**: Code snippets or log data should use the system's default monospace stack (Consolas, Monaco, or Courier New).

## Layout & Spacing

The layout follows a **Fluid Grid** model typical of the WordPress dashboard, where panels expand to fill the horizontal space available between the admin sidebar and the screen edge.

- **Margins**: A standard 20px margin is maintained around the main content area.
- **Panel Gaps**: Vertical spacing between cards/panels should be a consistent 20px.
- **Internal Padding**: White panels use 24px internal padding for a spacious, modern feel, while tighter data tables use 12px vertical padding per row.
- **Breakpoints**: 
  - Mobile (<782px): Side-by-side inputs stack vertically; horizontal padding reduces to 10px.
  - Tablet (782px - 960px): Standard WordPress sidebar collapses to icons; main panels remain fluid.

## Elevation & Depth

To maintain the "flat-plus" aesthetic of modern WordPress, this design system avoids heavy shadows and floating elements.

- **Low-Contrast Outlines**: Depth is primarily communicated through 1px solid borders (`#C3C4C7`). 
- **Tonal Layers**: The background-to-surface transition (`#F0F0F1` to `#FFFFFF`) is the primary method for defining content areas.
- **Subtle Shadows**: Only a single, very soft shadow level is permitted for active dropdowns or modals: `0 2px 4px rgba(0,0,0,0.05)`.
- **Interactive States**: Buttons and cards should use subtle background color shifts rather than shadow increases on hover.

## Shapes

The design system departs from the legacy 2px radius in favor of a modern **8px (0.5rem)** radius for all primary UI elements. This "Rounded" language softens the professional interface without making it feel overly playful.

- **Buttons & Inputs**: 8px corner radius.
- **Cards/Panels**: 8px corner radius.
- **Status Badges**: Use 4px (Soft) for smaller labels to maintain visual balance.
- **Selection Focus**: Focus rings should follow the 8px radius with a 2px offset.

## Components

### Buttons
- **Primary**: Solid `#2271B1` background, white text, 8px radius.
- **Secondary**: White background, `#2271B1` border and text.
- **Tertiary/Ghost**: No border or background; uses `#2271B1` text.

### Inputs & Toggles
- **Text Fields**: 1px border (`#C3C4C7`), 8px radius, white background. On focus, use a 2px blue outline.
- **Toggles**: Use the modern WordPress toggle style—pill-shaped track with a sliding circular thumb. Track is `#646970` when off and `#2271B1` when on.

### Cards & Panels
- **Container**: White background, 1px solid `#C3C4C7` border, 8px radius.
- **Header**: Optional light grey border-bottom (`#F0F0F1`) to separate title from content.

### Lists & Tables
- **WP-Table Style**: Alternating row stripes are not required; use subtle hover states (`#F6F7F7`) to highlight rows.
- **Icons**: Use Dashicons or compatible line-style icons (20px size) to represent security status, log types, and navigation.

### Status Badges (Chips)
- Compact, 4px radius, with background colors at 10% opacity of the status color and 100% opacity for the text (e.g., Success chip is light green with dark green text).