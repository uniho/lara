
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
    shouldForwardProp: key => !(['elevated', 'filled', 'outlined', 'float']).includes(key) 
  }),
};

//
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
    /* background-size: 0; */
    /* background-repeat: no-repeat; */
    /* background-position: 50% 50%; */
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

    /* 
      タッチデバイスなら hover アニメーションはしないようにする
      そうしないと、タッチ後に hover の状態で描画されたままてなってしまうため 
    */
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

export const asButtonStyle = props => {
  const isDark = window.colorScheme == 'dark';

  props = props || {};

  let border = 'none';
  let borderRadius = 0;
  let color = 'var(--style-palette-primary)';
  let bgcolor = 'inherit';
  let bgcolorHover = 'var(--style-palette-primary)';
  let bgimageHover = getLinearGradient(1 - 0.08);
  let bgcolorActive = 'var(--style-palette-primary)';
  let bgimageActive = getLinearGradient(1 - 0.1);
  let shadow = 'none';
  let disabled = {
    opacity: 0.38, 
    color:'var(--style-palette-on-surface)',    
  };
  if (isDark) {
    bgimageHover = getDarkLinearGradient(0.7);
    bgimageActive = getDarkLinearGradient(0.5);
  }  
  if (props.elevated) {
    color = 'var(--style-palette-primary)';
    bgcolor = 'var(--style-palette-surface-container-low)';
    shadow = '0 2px 5px 0 rgb(0 0 0/.14), 0 2px 10px 0 rgb(0 0 0/.1)';
    disabled.backgroundColor = 'inherit';
    disabled.backgroundImage = isDark ? getDarkLinearGradient(0.12) : getLinearGradient(1 - 0.12);
    disabled.boxShadow = 'none';
  }
  if (props.filled) {
    borderRadius = '4px';
    color = 'var(--style-palette-on-primary)';
    bgcolor = 'var(--style-palette-primary)';
    bgimageHover = getLinearGradient(0.2);
    bgimageActive = getLinearGradient(0.5);
    disabled.backgroundColor = 'inherit';
    disabled.backgroundImage = isDark ? getDarkLinearGradient(0.12) : getLinearGradient(1 - 0.12);
  }
  if (props.outlined) {
    border = 'solid 1px var(--style-palette-outline)';
    borderRadius = '4px';
    color = 'var(--style-palette-primary)';
    bgcolor = 'inherit';
  }
 
  const style = {
    // borderRadius: isDefault ? '2px' : 0,
    // boxShadow: isDefault ? '0 2px 5px 0 rgb(0 0 0/.14), 0 2px 10px 0 rgb(0 0 0/.1)' : 'none',
    padding: '8px 16px',
  };
  if (props.float) {
    style.width = '64px';
    style.height = '64px';
    style.borderRadius = '50%';
    style.padding = '0';
  }

  return [css`
    position: relative;
    background-image: none;
    /* background-size: 0; */
    /* background-repeat: no-repeat; */
    /* background-position: 50% 50%; */
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    /*font-family: Roboto;*/

    color: ${color};
    background-color: ${bgcolor};
    box-shadow: ${shadow};
    border: ${border};
    border-radius: ${borderRadius};
    /* transition: background-color .3s ease-out, background-image .3s ease-out;
    will-change: background-color, background-image; */

    /*
      タッチデバイスなら hover アニメーションはしないようにする
      そうしないと、タッチ後に hover の状態で描画されたままてなってしまうため 
    */
    @media (hover: hover) and (pointer: fine) {
      &:hover:not(:disabled):not(:active), &:focus-visible {
        background-color: ${bgcolorHover};
        background-image: ${bgimageHover}; 
      }
    }

    &:active:not(:disabled) {
      box-shadow: none;
      background-color: ${bgcolorActive};
      background-image: ${bgimageActive}; 
    }

  `, 
  style,
  props.disabled ? disabled : {cursor: 'pointer'}];
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

const getLinearGradient = (transparency) => {
  return `linear-gradient(rgb(255 255 255/${transparency}), rgb(255 255 255/${transparency}))`;
}

const getDarkLinearGradient = (transparency) => {
  return `linear-gradient(rgb(0 0 0/${transparency}), rgb(0 0 0/${transparency}))`;
}