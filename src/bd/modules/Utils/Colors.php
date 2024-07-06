<?php

namespace Utils;

// https://github.com/mexitek/phpColors
final class Colors
{
  public static function hex2rgb(string $color): array
  {
    $color = self::sanitizeHex($color);
    return [
      'R' => hexdec($color[0] . $color[1]),
      'G' => hexdec($color[2] . $color[3]),
      'B' => hexdec($color[4] . $color[5]),
    ];
  }

  public static function rgb2hex(array $rgb): string
  {
    // Make sure it's RGB
    if (empty($rgb) || !isset($rgb["R"], $rgb["G"], $rgb["B"])) {
      throw new Exception("Param was not an RGB array");
    }
    if (isset($rgba['A'])) {
      $rgb = self::rgba2rgb($rgb);
    }  
    return sprintf('#%02x%02x%02x', (int)$rgb['R'], (int)$rgb['G'], (int)$rgb['B']);
  }

  public static function string2rgb(string $color)
  {
    if (substr($color, 0, 1) == '#') {
      return self::hex2rgb($color);
    }
    if (preg_match('/rgba?\((.+)\)/', $color, $m)) {
      if (preg_match('{(.+)\s+(.+)\s+(.+)(?:\s*/\s*(.+))?}', $color, $m)) {
        $rgb['R'] = $m[1];
        $rgb['G'] = $m[2];
        $rgb['B'] = $m[3];
        if (isset($m[4]) && $m[4]) {
          $rgb['A'] = $m[4];
          $rgb = self::rgba2rgb($rgb);
        }
        return $rgb;
      }

      if (preg_match('/(.+),\s*(.+),\s*(.+)(?:,\s*(.+))?/', $color, $m)) {
        $rgb['R'] = $m[1];
        $rgb['G'] = $m[2];
        $rgb['B'] = $m[3];
        if (isset($m[4]) && $m[4]) {
          $rgb['A'] = $m[4];
          $rgb = self::rgba2rgb($rgb);
        }
        return $rgb;
      }
    }
  }

  public static function rgba2rgb(array $rgba): array {
    if (!isset($rgba['A'])) {
      return $rgba;
    }
    
    if (is_string($rgba['A'])) {
      if (substr($rgba['A'], 0, 1) == '.') {
        $rgba['A'] = '0' . $rgba['A'];
      } else if (substr($rgba['A'], -1, 1) == '%') {
        $rgba['A'] = ((float)substr($rgba['A'], 0, -1)) / 100;
      }
    }
    $a = (float)$rgba['A'];
    return [
      'R' => (int)(((1 - $a) + $a * $rgba['R']) * 255),
      'G' => (int)(((1 - $a) + $a * $rgba['G']) * 255),
      'B' => (int)(((1 - $a) + $a * $rgba['B']) * 255),
    ];
  }

  public static function hex2hsl(string $color): array
  {
    // Sanity check
    $color = self::sanitizeHex($color);

    // Convert HEX to DEC
    $R = hexdec($color[0] . $color[1]);
    $G = hexdec($color[2] . $color[3]);
    $B = hexdec($color[4] . $color[5]);

    $HSL = array();

    $var_R = ($R / 255);
    $var_G = ($G / 255);
    $var_B = ($B / 255);

    $var_Min = min($var_R, $var_G, $var_B);
    $var_Max = max($var_R, $var_G, $var_B);
    $del_Max = $var_Max - $var_Min;

    $L = ($var_Max + $var_Min) / 2;

    if ($del_Max == 0) {
      $H = 0;
      $S = 0;
    } else {
      if ($L < 0.5) {
          $S = $del_Max / ($var_Max + $var_Min);
      } else {
          $S = $del_Max / (2 - $var_Max - $var_Min);
      }

      $del_R = ((($var_Max - $var_R) / 6) + ($del_Max / 2)) / $del_Max;
      $del_G = ((($var_Max - $var_G) / 6) + ($del_Max / 2)) / $del_Max;
      $del_B = ((($var_Max - $var_B) / 6) + ($del_Max / 2)) / $del_Max;

      if ($var_R == $var_Max) {
          $H = $del_B - $del_G;
      } elseif ($var_G == $var_Max) {
          $H = (1 / 3) + $del_R - $del_B;
      } elseif ($var_B == $var_Max) {
          $H = (2 / 3) + $del_G - $del_R;
      }

      if ($H < 0) {
          $H++;
      }
      if ($H > 1) {
          $H--;
      }
    }

    $HSL['H'] = ($H * 360);
    $HSL['S'] = $S;
    $HSL['L'] = $L;

    return $HSL;
  }

  public static function hsl2hex(array $hsl = []): string
  {
    // Make sure it's HSL
    if (empty($hsl) || !isset($hsl["H"], $hsl["S"], $hsl["L"])) {
      throw new Exception("Param was not an HSL array");
    }

    list($H, $S, $L) = array($hsl['H'] / 360, $hsl['S'], $hsl['L']);

    if ($S == 0) {
      $r = $L * 255;
      $g = $L * 255;
      $b = $L * 255;
    } else {
      if ($L < 0.5) {
          $var_2 = $L * (1 + $S);
      } else {
          $var_2 = ($L + $S) - ($S * $L);
      }

      $var_1 = 2 * $L - $var_2;

      $r = 255 * self::hue2rgb($var_1, $var_2, $H + (1 / 3));
      $g = 255 * self::hue2rgb($var_1, $var_2, $H);
      $b = 255 * self::hue2rgb($var_1, $var_2, $H - (1 / 3));
    }

    // Convert to hex
    $r = dechex(round($r));
    $g = dechex(round($g));
    $b = dechex(round($b));

    // Make sure we get 2 digits for decimals
    $r = (strlen("" . $r) === 1) ? "0" . $r : $r;
    $g = (strlen("" . $g) === 1) ? "0" . $g : $g;
    $b = (strlen("" . $b) === 1) ? "0" . $b : $b;

    return '#' . $r . $g . $b;
  }

  public static function darken(string $color, int $amount): string
  {
    $rgb = is_array($color) ? $color : self::string2rgb($color);
    $color = self::rgb2hex($rgb);
    $hsl = self::darkenHsl(self::hex2hsl($color), $amount);
    return self::hsl2hex($hsl);
  }

  public static function lighten($color, int $amount): string
  {
    $rgb = is_array($color) ? $color : self::string2rgb($color);
    $color = self::rgb2hex($rgb);
    $hsl = self::lightenHsl(self::hex2hsl($color), $amount);
    return self::hsl2hex($hsl);
  }

  public static function isLight($color, int $lighterThan = 130): bool
  {
    $rgb = is_array($color) ? $color : self::string2rgb($color);
    return (($rgb['R'] * 299 + $rgb['G'] * 587 + $rgb['B'] * 114) / 1000 > $lighterThan);
  }

  public static function isDark($color, int $darkerThan = 130): bool
  {
    $rgb = is_array($color) ? $color : self::string2rgb($color);
    return (($rgb['R'] * 299 + $rgb['G'] * 587 + $rgb['B'] * 114) / 1000 <= $darkerThan);
  }

  // https://m2.material.io/design/color/dark-theme.html#properties
  public static function darkElevation($color, $dp) {
    $rgb = is_array($color) ? $color : self::string2rgb($color);
    $transparency = ((4.5 * log($dp + 1)) + 2) / 100;
    $rgb['R'] += (int)((0xff - $rgb['R']) * $transparency);
    $rgb['G'] += (int)((0xff - $rgb['G']) * $transparency);
    $rgb['B'] += (int)((0xff - $rgb['B']) * $transparency);
    return $rgb;
  }

  private static function sanitizeHex(string $hex): string
  {
    // Strip # sign if it is present
    $color = str_replace("#", "", $hex);

    // Validate hex string
    if (!preg_match('/^[a-fA-F0-9]+$/', $color)) {
      throw new Exception("HEX color does not match format");
    }

    // Make sure it's 6 digits
    if (strlen($color) === 3) {
      $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
    } else if (strlen($color) !== 6) {
      throw new Exception("HEX color needs to be 6 or 3 digits long");
    }

    return $color;
  }

  private static function hue2rgb(float $v1, float $v2, float $vH): float
  {
    if ($vH < 0) {
      ++$vH;
    }

    if ($vH > 1) {
      --$vH;
    }

    if ((6 * $vH) < 1) {
      return ($v1 + ($v2 - $v1) * 6 * $vH);
    }

    if ((2 * $vH) < 1) {
      return $v2;
    }

    if ((3 * $vH) < 2) {
      return ($v1 + ($v2 - $v1) * ((2 / 3) - $vH) * 6);
    }

    return $v1;
  }

  private static function darkenHsl(array $hsl, int $amount): array
  {
    // Check if we were provided a number
    if ($amount) {
      $hsl['L'] = ($hsl['L'] * 100) - $amount;
      $hsl['L'] = ($hsl['L'] < 0) ? 0 : $hsl['L'] / 100;
    } else {
      // We need to find out how much to darken
      $hsl['L'] /= 2;
    }

    return $hsl;
  }

  private static function lightenHsl(array $hsl, int $amount): array
  {
    // Check if we were provided a number
    if ($amount) {
      $hsl['L'] = ($hsl['L'] * 100) + $amount;
      $hsl['L'] = ($hsl['L'] > 100) ? 1 : $hsl['L'] / 100;
    } else {
      // We need to find out how much to lighten
      $hsl['L'] += (1 - $hsl['L']) / 2;
    }

    return $hsl;
  }
}
