<?php

/* M3 Example Midnight ~ https://rydmike.com/flexcolorscheme/themesplayground-latest/ */

// Surface Container について
// https://material.io/blog/tone-based-surface-color-m3

$primary = "#00296B";
$onPrimary = "#fff";
$primaryContainer = "#578ee6";
$onPrimaryContainer = "#fff";
$secondary = "#5C5C95";
$onSecondary = "#fff";
$secondaryContainer = "#a9a9de";
$onSecondaryContainer = "#fff";
$surface = "#eeeeee";
$surfaceContainerBright = \Utils\Colors::lighten($surface, 1);
$surfaceContainerLow = \Utils\Colors::lighten($surface, -1); // = darken(1)
$surfaceContainer = \Utils\Colors::lighten($surface, -2);
$surfaceContainerHigh = \Utils\Colors::lighten($surface, -4);
$surfaceContainerHighest = \Utils\Colors::lighten($surface, -8);
$surfaceDim = \Utils\Colors::lighten($surface, -10);
$onSurface = "#1B1B1F";
$onSurfaceVariant = \Utils\Colors::lighten($onSurface, 40);

$primary_dark = "#4fb3d1";
$onPrimary_dark = "#1B1B1F";
$primaryContainer_dark = "#84bbe8";
$onPrimaryContainer_dark = "#1B1B1F";
$secondary_dark = "#bd93f9";
$onSecondary_dark = "#1B1B1F";
$secondaryContainer_dark = "#ce93d8";
$onSecondaryContainer_dark = "#1B1B1F";
$surface_dark = "#282a36";
$surfaceContainerBright_dark = \Utils\Colors::darken($surface_dark, 1);
$surfaceContainerLowest_dark = \Utils\Colors::darken($surface_dark, 4);
$surfaceContainerLow_dark = \Utils\Colors::lighten($surface_dark, 1);
$surfaceContainer_dark = \Utils\Colors::lighten($surface_dark, 2);
$surfaceContainerHigh_dark = \Utils\Colors::lighten($surface_dark, 4);
$surfaceContainerHighest_dark = \Utils\Colors::lighten($surface_dark, 8);
$surfaceDim_dark = \Utils\Colors::lighten($surface_dark, 10);
$onSurface_dark = "#F8F8F2";
$onSurfaceVariant_dark = "#bcc2cd";

// scrim は opacity 32%
// see https://m3.material.io/styles/elevation/applying-elevation#92b9fb39-f0c4-4829-8e4d-97ac512976aa

return "

:root {
  --style-palette-primary: {$primary};
  --style-palette-on-primary: {$onPrimary};
  --style-palette-primary-container: {$primaryContainer};
  --style-palette-on-primary-container: {$onPrimaryContainer};
  --style-palette-secondary: {$secondary};
  --style-palette-on-secondary: {$onSecondary};
  --style-palette-secondary-container: {$secondaryContainer};
  --style-palette-on-secondary-container: {$onSecondaryContainer};
  --style-palette-surface: {$surface};
  --style-palette-surface-dim: {$surfaceDim};
  --style-palette-surface-bright: {$surfaceContainerBright};
  --style-palette-surface-container-lowest: #fff;
  --style-palette-surface-container-low: {$surfaceContainerLow};
  --style-palette-surface-container: {$surfaceContainer};
  --style-palette-surface-container-high: {$surfaceContainerHigh};
  --style-palette-surface-container-highest: {$surfaceContainerHighest};
  --style-palette-on-surface: {$onSurface};
  --style-palette-on-surface-variant: {$onSurfaceVariant};
  --style-palette-outline: #757780;
  --style-palette-outline-variant: #C5C6D0;
}

:root[data-color-scheme=\"dark\"] {
  color-scheme: dark;
  --style-palette-primary: {$primary_dark};
  --style-palette-on-primary: {$onPrimary_dark};
  --style-palette-primary-container: {$primaryContainer_dark};
  --style-palette-on-primary-container: {$onPrimaryContainer_dark};
  --style-palette-secondary: {$secondary_dark};
  --style-palette-on-secondary: {$onSecondary_dark};
  --style-palette-secondary-container: {$secondaryContainer_dark};
  --style-palette-on-secondary-container: {$onSecondaryContainer_dark};
  --style-palette-surface: {$surface_dark};
  --style-palette-surface-dim: {$surfaceDim_dark};
  --style-palette-surface-bright: {$surfaceContainerBright_dark};
  --style-palette-surface-container-lowest: {$surfaceContainerLowest_dark};
  --style-palette-surface-container-low: {$surfaceContainerLow_dark};
  --style-palette-surface-container: {$surfaceContainer_dark};
  --style-palette-surface-container-high: {$surfaceContainerHigh_dark};
  --style-palette-surface-container-highest: {$surfaceContainerHighest_dark};
  --style-palette-on-surface: {$onSurface_dark};
  --style-palette-on-surface-variant: {$onSurfaceVariant_dark};
  --style-palette-outline: #757780;
  --style-palette-outline-variant: #C5C6D0;
}

";
