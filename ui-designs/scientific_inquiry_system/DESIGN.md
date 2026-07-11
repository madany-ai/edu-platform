---
name: Scientific Inquiry System
colors:
  surface: '#f7f9fc'
  surface-dim: '#d8dadd'
  surface-bright: '#f7f9fc'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f7'
  surface-container: '#eceef1'
  surface-container-high: '#e6e8eb'
  surface-container-highest: '#e0e3e6'
  on-surface: '#191c1e'
  on-surface-variant: '#3f4945'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f4'
  outline: '#707975'
  outline-variant: '#bfc9c4'
  surface-tint: '#29695b'
  primary: '#00342b'
  on-primary: '#ffffff'
  primary-container: '#004d40'
  on-primary-container: '#7ebdac'
  inverse-primary: '#94d3c1'
  secondary: '#006875'
  on-secondary: '#ffffff'
  secondary-container: '#00e3fd'
  on-secondary-container: '#00616d'
  tertiary: '#19303b'
  on-tertiary: '#ffffff'
  tertiary-container: '#304751'
  on-tertiary-container: '#9cb5c1'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#afefdd'
  primary-fixed-dim: '#94d3c1'
  on-primary-fixed: '#00201a'
  on-primary-fixed-variant: '#065043'
  secondary-fixed: '#9cf0ff'
  secondary-fixed-dim: '#00daf3'
  on-secondary-fixed: '#001f24'
  on-secondary-fixed-variant: '#004f58'
  tertiary-fixed: '#cde6f4'
  tertiary-fixed-dim: '#b1cad7'
  on-tertiary-fixed: '#051e28'
  on-tertiary-fixed-variant: '#334a55'
  background: '#f7f9fc'
  on-background: '#191c1e'
  surface-variant: '#e0e3e6'
typography:
  display-lg:
    fontFamily: IBM Plex Sans
    fontSize: 48px
    fontWeight: '700'
    lineHeight: '1.2'
  headline-lg:
    fontFamily: IBM Plex Sans
    fontSize: 32px
    fontWeight: '600'
    lineHeight: '1.3'
  headline-lg-mobile:
    fontFamily: IBM Plex Sans
    fontSize: 28px
    fontWeight: '600'
    lineHeight: '1.3'
  headline-md:
    fontFamily: IBM Plex Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.4'
  body-lg:
    fontFamily: IBM Plex Sans
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: IBM Plex Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  label-md:
    fontFamily: IBM Plex Sans
    fontSize: 14px
    fontWeight: '500'
    lineHeight: '1.4'
    letterSpacing: 0.02em
  caption:
    fontFamily: IBM Plex Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: '1.4'
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  container-margin: 24px
  gutter: 20px
  section-padding: 64px
  card-padding: 24px
---

## Brand & Style

This design system is engineered for middle school students, balancing academic rigor with the excitement of scientific discovery. The brand personality is professional, modern, and high-tech, positioning the platform as a sophisticated laboratory for learning rather than a traditional classroom.

The visual style utilizes **Glassmorphism** and **Modern Corporate** aesthetics. By combining frosted-glass surfaces with a precise, systematic layout, the UI creates a sense of depth and transparency that mirrors the clarity of the scientific method. The interface should feel breathable and organized, reducing cognitive load for students tackling complex subjects like chemistry, physics, and biology.

## Colors

The palette is anchored by a **Deep Teal** primary, providing a grounded, authoritative foundation that conveys trust and depth. This is contrasted by a **Vibrant Cyan** secondary, used for interactive elements and highlights to inject energy and a "tech-forward" feel. 

- **Primary (Deep Teal):** Used for navigation, headings, and primary brand moments.
- **Secondary (Vibrant Cyan):** Reserved for primary calls-to-action, progress indicators, and active states.
- **Surface/Neutral:** A clean white background is supplemented by soft, cool grays (#F5F7FA) to define container boundaries without introducing visual clutter.
- **Success/Warning:** Standard semantic colors should be adjusted to match the cool-toned palette (e.g., an emerald green for success).

## Typography

The typography system relies on **IBM Plex Sans**, chosen for its exceptional support for Arabic script and its systematic, technical personality. The font's high legibility ensures that scientific terminology is easily digestible for students in Middle 1, 2, and 3.

As this is an Arabic-first platform, the layout must be optimized for **Right-to-Left (RTL)** reading patterns. Line heights are slightly increased (1.6 for body text) to accommodate the vertical characteristics of Arabic characters. Bold weights are used sparingly for emphasis and clear information hierarchy in course navigation.

## Layout & Spacing

The design system employs a **12-column fluid grid** for desktop and tablet, transitioning to a **single-column stack** for mobile. 

- **Desktop (1200px+):** 12 columns with 20px gutters and 24px outer margins.
- **Tablet (768px - 1199px):** 8 columns with 16px gutters.
- **Mobile (Up to 767px):** 4 columns with 12px gutters.

Spacing follows an 8px base unit. Larger components like course cards should use the `section-padding` unit for vertical breathing room to maintain the professional, minimalist aesthetic. All layout logic must be mirrored for RTL support.

## Elevation & Depth

This design system utilizes **Glassmorphism** as the primary method for indicating hierarchy and depth. 

1.  **Base Layer:** Solid white or neutral-gray background.
2.  **Middle Layer (Cards/Panels):** Translucent white surfaces (80-90% opacity) with a `20px` backdrop blur. These elements feature a very thin, low-contrast 1px border (#FFFFFF 30%) to define edges.
3.  **Top Layer (Modals/Popovers):** Higher contrast glass effects with soft, ambient shadows (Blur: 30px, Y: 10px, Color: Deep Teal at 5% opacity) to provide a "floating" sensation.

Depth is used to separate content (cards) from the global workspace (background), ensuring that the focus remains on the learning material.

## Shapes

The shape language is defined by large, inviting radii. Following the `rounded-2` setting:
- **Small elements (Chips, Tags):** 0.5rem (8px).
- **Interactive elements (Buttons, Inputs):** 1rem (16px).
- **Large containers (Course Cards, Modals):** 1.5rem (24px) for a soft, friendly appearance that balances the professional typography.

Buttons should never be sharp; the rounded corners signify a modern, accessible educational environment.

## Components

### Buttons
Primary buttons use the **Vibrant Cyan** gradient or solid fill with white text. They should have a subtle inner glow to enhance the "glassy" appearance. Hover states should include a slight increase in saturation and a soft shadow.

### Course Cards
Cards are the primary container for lessons. They must feature a glass-morphic background, a large top-aligned scientific icon (e.g., a stylized atom for physics), and clear typography. Use a 24px padding internal to the card.

### Navigation
The navigation bar is a fixed-top frosted glass element. Active links are indicated by a Deep Teal underline or weight change. The layout must be strictly RTL, with the logo on the right and search/profile on the left.

### Chips & Badges
Used for labeling grade levels (Middle 1, 2, 3) or subjects (Chemistry, Biology). These use a low-opacity Deep Teal background with high-contrast text to ensure they don't compete with primary actions.

### Input Fields
Inputs are clean with a 1px soft gray border, turning Vibrant Cyan on focus. The backdrop is slightly translucent to maintain the glassmorphism theme across the entire application.