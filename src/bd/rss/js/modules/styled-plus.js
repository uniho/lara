
import {styled, Styled, css, cx, keyframes} from 'modules/styled.js'

//
export const Sx = {
  div: Styled('div'),
  span: Styled('span'),
  flex: Styled('div', {display:'flex'}),
  flexCol: Styled('div', {display:'flex', flexDirection: 'column'}),
  inlineFlex: Styled('div', {display:'inline-flex'}),
  grid: Styled('div', {display:'grid'}),
  button: Styled('button', props => asButtonStyle(props), {
    shouldForwardProp: key => !(['elevated', 'filled', 'outlined', 'float', 'tonal', 'unfocus']).includes(key) 
  }),
};

// deprecated
export const asButtonStyleOrg = props => {
  props = props || {};
  const isDefault = ('default' in props) && !props.disabled;
  const style = {
    borderRadius: isDefault ? '2px' : 0,
    boxShadow: isDefault ? '0 2px 5px 0 rgb(0 0 0/.14), 0 2px 10px 0 rgb(0 0 0/.1)' : 'none',
    padding: '8px 16px',
  };
  if (props.float) {
    style.width = '64px';
    style.height = '64px';
    style.borderRadius = '50%';
    style.boxShadow = '0 2px 5px 0 rgb(0 0 0/.14), 0 2px 10px 0 rgb(0 0 0/.1)';
    style.padding = '0';
  }

  const color = props.color || 'var(--style-palette-on-primary)';
  const bgcolor = props.bgcolor || 'var(--style-palette-primary)';
  const borderColor = 'var(--style-palette-outline)';

  return [css`
    position: relative;
    background-image: none;
    //background-size: 0;
    //background-repeat: no-repeat;
    //background-position: 50% 50%;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    /*font-family: Roboto;*/

    color: ${isDefault || props.float ? color : 'inherit'};
    background-color: ${('default' in props) || props.float ? bgcolor : 'inherit'};
    border: ${props.outlined ? `solid 1px ${borderColor}` : 'none'};
    transition: background-image .3s ease-out, box-shadow .3s ease-out;
    will-change: background-image, box-shadow;

    // タッチデバイスなら hover アニメーションはしないようにする
    // そうしないと、タッチ後に hover の状態で描画されたままてなってしまうため
    @media (hover: hover) and (pointer: fine) {
      &:hover:not(:disabled):not(:active), &:focus-visible {
        ${props.mode === 'dark' ? 
          `background-image: ${darkElevation(false, 3)};` : 
          `box-shadow: var(--style-shadows-5);`
        }
      }
    }

    &:active:not(:disabled) {
      box-shadow: none;
    }

  `, 
  style,
  props.disabled ? {opacity: 0.38} : {cursor: 'pointer'}];
};

// active については、標準仕様より変化量を多めにした
// props.unfocus でクリック後にフォーカスがあたらないボタンになる
export const asButtonStyle = props => {
  props = props || {};
  const propsStyle = typeof props.style == 'object' ? props.style : {};
  propsStyle.base = typeof propsStyle.base == 'object' ? propsStyle.base : {}; 
  propsStyle.hover = typeof propsStyle.hover == 'object' ? propsStyle.hover : {}; 
  propsStyle.focus = typeof propsStyle.focus == 'object' ? propsStyle.focus : {}; 
  propsStyle.active = typeof propsStyle.active == 'object' ? propsStyle.active : {}; 
  propsStyle.disabled = typeof propsStyle.disabled == 'object' ? propsStyle.disabled : {}; 

  const style = Object.assign(
    {
      // default is text button
      padding: '8px 16px',
      border: 'none',
      borderRadius: '2px',
      color: 'var(--style-palette-primary)',
      backgroundColor: 'inherit',
      boxShadow: 'none',
    }, 
    propsStyle.base 
  );

  const hover = Object.assign({
    // backgroundColor: 'rgb(var(--style-palette-primary-channel) /.08)',
    boxShadow: '0 0 0 1px currentColor',
  }, propsStyle.hover);
  const focus = Object.assign({
    backgroundColor: 'color-mix(in srgb, currentColor 10%, transparent)',
  }, propsStyle.focus);
  const active = Object.assign({
    backgroundColor: 'color-mix(in srgb, currentColor 20%, transparent)',
  }, propsStyle.active);
  const disabled = Object.assign({
    // color: 'rgb(var(--style-palette-on-surface-channel) /.38)',
    color: 'var(--style-palette-on-surface)',
    opacity: '.38',
    boxShadow: 'none',
  }, propsStyle.disabled);

  if (props.elevated) {
    if (!propsStyle.base.borderRadius)
      style.borderRadius = '0';
    if (!propsStyle.base.border)
      style.border = 'none';
    if (!propsStyle.base.color)
      style.color = 'var(--style-palette-primary)';
    if (!propsStyle.base.backgroundColor)
      style.backgroundColor = 'var(--style-palette-surface-container-low)';
    if (!propsStyle.base.boxShadow) 
      style.boxShadow = 'var(--style-shadows-2)'; // Level1 = 1dp
    if (!propsStyle.hover.border)
      hover.border = 'none';
    // if (!propsStyle.hover.backgroundColor) 
    //   hover.backgroundColor = `color-mix(in srgb, ${style.backgroundColor}, var(--style-palette-primary) 8%)`;
    if (!propsStyle.hover.boxShadow)
      // hover.boxShadow = 'var(--style-shadows-6)'; // Level2 = 3dp
      // hover.boxShadow = 'var(--style-shadows-6), 0 0 4px color-mix(in srgb, var(--style-palette-on-surface) 50%, transparent)';
      hover.boxShadow = `var(--style-shadows-6), 0 0 0 1px ${style.backgroundColor}`;
    if (!propsStyle.focus.backgroundColor)
      focus.backgroundColor = `color-mix(in srgb, ${style.backgroundColor}, var(--style-palette-primary) 10%)`;
    if (!propsStyle.active.backgroundColor)
      active.backgroundColor = `color-mix(in srgb, ${style.backgroundColor}, var(--style-palette-primary) 20%)`;
    if (!propsStyle.active.boxShadow)
      active.boxShadow = 'none';
    // if (!propsStyle.disabled.backgroundColor)
    //   disabled.backgroundColor = 'rgb(var(--style-palette-on-surface-channel) /.12)';
  }
  if (props.filled) {
    if (!propsStyle.base.border)
      style.border = 'none';
    if (!propsStyle.base.borderRadius)
      style.borderRadius = '4px';
    if (!propsStyle.base.color)
      style.color = 'var(--style-palette-on-primary)';
    if (!propsStyle.base.backgroundColor)
      style.backgroundColor = 'var(--style-palette-primary)';
    if (!propsStyle.hover.border)
      hover.border = 'none';
    if (!propsStyle.hover.backgroundColor)
      hover.backgroundColor = `color-mix(in srgb, ${style.backgroundColor}, var(--style-palette-on-primary) 8%)`;
    if (!propsStyle.hover.boxShadow)
      // hover.boxShadow = 'var(--style-shadows-2)'; // Level1 = 1dp
      // hover.boxShadow = 'var(--style-shadows-2), 0 0 4px var(--style-palette-on-surface)';
      hover.boxShadow = `0 0 0 1px ${style.backgroundColor}`;
    if (!propsStyle.focus.backgroundColor)
      focus.backgroundColor = `color-mix(in srgb, ${style.backgroundColor}, var(--style-palette-on-primary) 10%)`;
    if (!propsStyle.active.backgroundColor)
      active.backgroundColor = `color-mix(in srgb, ${style.backgroundColor}, var(--style-palette-on-primary) 20%)`;
    if (!propsStyle.disabled.backgroundColor)
      disabled.backgroundColor = 'var(--style-palette-surface-container-low)';
  }
  if (props.tonal) {
    if (!propsStyle.base.border)
      style.border = 'none';
    if (!propsStyle.base.borderRadius)
      style.borderRadius = '4px';
    if (!propsStyle.base.color)
      style.color = 'var(--style-palette-on-secondary-container)';
    if (!propsStyle.base.backgroundColor)
      style.backgroundColor = 'var(--style-palette-secondary-container)';
    if (!propsStyle.hover.border)
      hover.border = 'none';
    if (!propsStyle.hover.backgroundColor)
      hover.backgroundColor = `color-mix(in srgb, ${style.backgroundColor}, var(--style-palette-on-secondary-container) 8%)`;
    if (!propsStyle.hover.boxShadow)
      // hover.boxShadow = 'var(--style-shadows-2)'; // Level1 = 1dp
      // hover.boxShadow = 'var(--style-shadows-2), 0 0 4px var(--style-palette-on-surface)';
      hover.boxShadow = `0 0 0 1px ${style.backgroundColor}`;
    if (!propsStyle.focus.backgroundColor)
      focus.backgroundColor = `color-mix(in srgb, ${style.backgroundColor}, var(--style-palette-on-secondary-container) 10%)`;
    if (!propsStyle.active.backgroundColor)
      active.backgroundColor = `color-mix(in srgb, ${style.backgroundColor}, var(--style-palette-on-secondary-container) 20%)`;
    if (!propsStyle.disabled.backgroundColor)
      disabled.backgroundColor = 'var(--style-palette-surface-container-low)';
  }
  if (props.outlined) {
    if (!propsStyle.base.border)
      style.border = 'solid 1px var(--style-palette-outline)';
    if (!propsStyle.base.borderRadius)
      style.borderRadius = '4px';
    if (!propsStyle.base.color)
      style.color = 'var(--style-palette-primary)';
    if (!propsStyle.base.backgroundColor)
      style.backgroundColor = 'inherit';
    // if (!propsStyle.hover.backgroundColor)
    //   hover.backgroundColor = 'color-mix(in srgb, currentColor 8%, transparent)';
    if (!propsStyle.hover.boxShadow)
      // hover.boxShadow = `0 2px 4px 0 var(--style-palette-outline), 0 2px 6px 0 var(--style-palette-outline)`;
      hover.boxShadow = `0 0 0 1px var(--style-palette-outline)`;
    if (!propsStyle.disabled.backgroundColor)
      disabled.borderColor = 'var(--style-palette-on-surface)';
  }

  if (props.float) {
    if (!propsStyle.base.minWidth)
      style.minWidth = '64px';
    if (!propsStyle.base.maxWidth)
      style.maxWidth = '64px';
    if (!propsStyle.base.height)
      style.height = '64px';
    if (!propsStyle.base.borderRadius)
      style.borderRadius = '50%';
    if (!propsStyle.base.padding)
      style.padding = '0';
  }

  return [
    css`
      //position: relative;
      //background-image: none;
      //background-size: 0;
      //background-repeat: no-repeat;
      //background-position: 50% 50%;
      //line-height: 1;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      /*font-family: Roboto;*/

      /* transition: background-color .3s ease-out, background-image .3s ease-out;
      will-change: background-color, background-image; */
      transition: background-color .3s cubic-bezier(.2,0,0,1);
      will-change: background-color;

      &:not(:disabled) {
        cursor: pointer;
      }
    `,
    style,
    {
      '@media (hover: hover) and (pointer: fine)': {
        // タッチデバイスなら hover アニメーションはしないようにする
        // そうしないと、タッチ後に hover の状態で描画されたままてなってしまうため
        '&:hover:not(:disabled):not(:active)': hover,
      },
      '&:focus' : props.unfocus ? {} : focus,
      '&:active:not(:disabled)' : active,
      '&:disabled': disabled,
    }, 
  ];
};

//
export const hexToRgbRaw = (hex) => {
  // Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
  const shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
  hex = hex.replace(shorthandRegex, (m, r, g, b) => {
    return r + r + g + g + b + b;
  });

  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  if (!result) return result;
  return [
    parseInt(result[1], 16),
    parseInt(result[2], 16),
    parseInt(result[3], 16),
  ];
}

// deprecated
// https://m2.material.io/design/color/dark-theme.html#properties
export const darkElevation = (base = false, dp = 0) => {
  if (!dp) {
    if (base === false) {
      return 'none';
    }
    return base;
  }
  const transparency = getElevationTansparency(dp);
  if (base === false) {
    return `linear-gradient(rgb(255 255 255/${transparency}), rgb(255 255 255/${transparency}))`;
  }
  const rgb = hexToRgbRaw(base);
  rgb[0] += parseInt((0xff - rgb[0]) * transparency);
  rgb[1] += parseInt((0xff - rgb[1]) * transparency);
  rgb[2] += parseInt((0xff - rgb[2]) * transparency);
  return rgbToHex(rgb[0], rgb[1], rgb[2]);
}

// deprecated
export const lightElevation = (base = false, dp = 0) => {
  if (!dp) {
    if (base === false) {
      return 'none';
    }
    return base;
  }
  const transparency = getElevationTansparency(dp);
  if (base === false) {
    return `linear-gradient(rgb(255 255 255/${transparency}), rgb(255 255 255/${transparency}))`;
  }
  const rgb = hexToRgbRaw(base);
  rgb[0] += parseInt(rgb[0] + rgb[0] * transparency);
  rgb[1] += parseInt(rgb[1] + rgb[1] * transparency);
  rgb[2] += parseInt(rgb[2] + rgb[2] * transparency);
  return rgbToHex(rgb[0], rgb[1], rgb[2]);
}

const getElevationTansparency = (dp = 0) => {
  if (!dp) {
    return 0;
  }
  return ((4.5 * Math.log(dp + 1)) + 2) / 100;
}

// Convert colors in RGB format to Hex format.
export const rgbToHex = (r, g, b) => {
  return "#" + (1 << 24 | r << 16 | g << 8 | b).toString(16).slice(1);
}

export const hexToRgb = (hex) => {
  const result = hexToRgbRaw(hex);
  if (!result) return result;
  return `${result[0]} ${result[1]} ${result[2]}`;
}

export const alphaBlend = (hexSrc, alpha, hexDst = '#fff') => {
  const src = hexToRgbRaw(hexSrc);
  const dst = hexToRgbRaw(hexDst);
  return rgbToHex(src[0] * alpha + dst[0] * (1 - alpha), src[1] * alpha + dst[1] * (1 - alpha), src[2] * alpha + dst[2] * (1 - alpha));
}

export const lighten = (hex, amount) => {
  return hsl2hex(lightenHsl(hex2hsl(hex), amount));
}

export const darken = (hex, amount) => {
  return hsl2hex(darkenHsl(hex2hsl(hex), amount));
}

const hex2hsl = hex => {
  const rgb = hexToRgbRaw(hex);

  const var_R = rgb[0] / 255;
  const var_G = rgb[1] / 255;
  const var_B = rgb[2] / 255;

  const var_Min = Math.min(var_R, var_G, var_B);
  const var_Max = Math.max(var_R, var_G, var_B);
  const del_Max = var_Max - var_Min;

  let h = 0, s = 0, l = (var_Max + var_Min) / 2;

  if (del_Max != 0) {
    if (l < 0.5) {
      s = del_Max / (var_Max + var_Min);
    } else {
      s = del_Max / (2 - var_Max - var_Min);
    }

    const del_R = (((var_Max - var_R) / 6) + (del_Max / 2)) / del_Max;
    const del_G = (((var_Max - var_G) / 6) + (del_Max / 2)) / del_Max;
    const del_B = (((var_Max - var_B) / 6) + (del_Max / 2)) / del_Max;

    if (var_R == var_Max) {
      h = del_B - del_G;
    } else if (var_G == var_Max) {
      h = (1 / 3) + del_R - del_B;
    } else if (var_B == var_Max) {
      h = (2 / 3) + del_G - del_R;
    }

    if (h < 0) {
      h++;
    }
    if (h > 1) {
      h--;
    }
  }

  h *= 360;
  return {h, s, l};
}

const hsl2hex = hsl => {
  const H = hsl.h / 360, S = hsl.s, L = hsl.l;
  let r = L * 255, g = L * 255, b = L * 255; 
  if (S != 0) {
    let var_1, var_2;
    if (L < 0.5) {
      var_2 = L * (1 + S);
    } else {
      var_2 = (L + S) - (S * L);
    }

    var_1 = 2 * L - var_2;

    r = 255 * hue2rgb(var_1, var_2, H + (1 / 3));
    g = 255 * hue2rgb(var_1, var_2, H);
    b = 255 * hue2rgb(var_1, var_2, H - (1 / 3));
  }

  return rgbToHex(Math.round(r), Math.round(g), Math.round(b));
}

const hue2rgb = (v1, v2, vH) => {
  if (vH < 0) {
    ++vH;
  }

  if (vH > 1) {
    --vH;
  }

  if ((6 * vH) < 1) {
    return (v1 + (v2 - v1) * 6 * vH);
  }

  if ((2 * vH) < 1) {
    return v2;
  }

  if ((3 * vH) < 2) {
    return (v1 + (v2 - v1) * ((2 / 3) - vH) * 6);
  }

  return v1;
}

const lightenHsl = (hsl, amount) => {
  // Check if we were provided a number
  if (amount) {
    hsl.l = (hsl.l * 100) + amount;
    hsl.l = (hsl.l > 100) ? 1 : hsl.l / 100;
  } else {
    // We need to find out how much to lighten
    hsl.l += (1 - hsl.l) / 2;
  }

  return hsl;
}

const darkenHsl = (hsl, amount) => {
  // Check if we were provided a number
  if (amount) {
    hsl.l = (hsl.l * 100) - amount;
    hsl.l = (hsl.l < 0) ? 0 : hsl.l / 100;
  } else {
    // We need to find out how much to darken
    hsl.l /= 2;
  }

  return hsl;
}

//
export const colorMixLinearGradient = (base, state = false) => {
  if (!state) state = base;
  return `linear-gradient(${base}, ${state})`;
}
