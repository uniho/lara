
import {parseArgs} from "node:util";
import {writeFile, readFile} from 'node:fs/promises';
import process from 'node:process';
import {transform, Features} from 'lightningcss';

try {
  const args = parseArgs({
    options: {
      outfile: {
        type: "string",
        default: "",
      },
      minify: {
        type: "boolean",
        default: false,
      },
      sourcemap: {
        type: "boolean",
        default: false,
      },
    },
    allowPositionals: true,
  });

  const input = args.positionals[0];
  const output = args.values.outfile;

  let src = '';
  if (input) {
    src = await readFile(input, 'utf8');
  } else {  
    if (process.platform == 'win32') {
      process.stdin.setEncoding('utf8');
      for await (const chunk of process.stdin) src += chunk;
    } else {
      src = await readFile("/dev/stdin", 'utf8');
    }
    // console.error('Usage: <in file name> [--outfile=<out file name>] [--minify]');
  }

  // // Strip Single Line Comments
  // // LightningCSS doesn't support Single Line Comments on v1.25.1
  // src = src.replace(/(\/\*(?:(?!\*\/)[\s\S])*\*\/|\"(?:(?!(?<!\\)\").)*\"|\'(?:(?!(?<!\\)\').)*\')|\/\/.*/g, '$1');

  const getValue = arg => {
    switch (arg.type) {
      // case 'env':
      //   return {type: 'string', value: palettes[arg.value.name.ident]};
      case 'color':
        return arg.value;

      case 'token':
        switch (arg.value.type) {
          case 'at-keyword':
            const c = declared.get(arg.value.value)?.[0]?.value ?? '!!ERROR!!';
            return c; 
          case 'number':
          case 'string':
            return arg.value; 
        }
        throw new Error('unknown token'); 

      case 'function':
        // もっといい方法がありそう
        switch (arg.value.name) {
          case 'channel': return channel(arg.value);
          case 'lighten': return rawLighten(arg.value);
          case 'darken': return rawDarken(arg.value);
        }
        throw new Error('unknown function'); 

    }  
    
    // 
    return arg.value.value;
  };

  const channel = info => {
    const color = getValue(info.arguments[0]);
    // if (color.type == 'string') {
    //   const shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
    //   const hex = color.value.replace(shorthandRegex, (m, r, g, b) => {
    //     return r + r + g + g + b + b;
    //   });

    //   const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    //   // if (!result) return result;

    //   return (
    //     { raw: `${parseInt(result[1], 16)} ${parseInt(result[2], 16)} ${parseInt(result[3], 16)}` }
    //   );
    // } 
    return (
      { raw: `${color.r} ${color.g} ${color.b}` }
    );
  }

  const rawLighten = info => {
    const color = getValue(info.arguments[0]);
    const amount = getValue(info.arguments[2]).value;
    return hsl2rgb(lightenHsl(rgb2hsl(color), amount));
  }

  const rawDarken = info => {
    const color = getValue(info.arguments[0]);
    const amount = getValue(info.arguments[2]).value;
    return hsl2rgb(darkenHsl(rgb2hsl(color), amount));
  }

  const lighten = info => {
    const rgb = rawLighten(info);
    return ({raw: `rgb(${rgb.r} ${rgb.g} ${rgb.b}/${rgb.alpha})`});
  }

  const darken = info => {
    const rgb = rawDarken(info);
    return ({raw: `rgb(${rgb.r} ${rgb.g} ${rgb.b}/${rgb.alpha})`});
  }

  const rgb2hsl = color => {
    if (color.type != 'rgb') return color;

    const var_R = color.r / 255;
    const var_G = color.g / 255;
    const var_B = color.b / 255;

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
    return {type: 'hsl', h, s, l, alpha: color.alpha};
  }

  const hsl2rgb = hsl => {
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

    r = Math.round(r);
    g = Math.round(g);
    b = Math.round(b);

    return {type: 'rgb', r, g, b, alpha: hsl.alpha};
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
  const declared = new Map();
  
  //
  let { code, map } = transform({
    filename: input,
    code: Buffer.from(src),
    minify: args.values.minify,
    include: Features.Colors | Features.Nesting | Features.MediaQueries,
    sourceMap: args.values.sourcemap,
    visitor: {
      
      Rule: {
        unknown(rule) {
          declared.set(rule.name, rule.prelude);
          return [];
        }
      },
      
      Token: {
        'at-keyword'(token) {
          if (declared.has(token.value)) {
            return declared.get(token.value);
          }
          throw new Error('unknown keyword: @' + token.value)
        }
      },
      
      Function: {
        channel(info) {
          return channel(info);
        },

        lighten(info) {
          return lighten(info);          
        },

        darken(info) {
          return darken(info);          
        },

      },
    },
  });
  
  if (!output) {
    console.log(code.toString());
    console.error('done!');
    process.exit(0);
  }

  let codeString = code.toString();
  if (args.values.sourcemap) {
    await writeFile(output+'.map', map.toString(), 'utf8');
    codeString += '\n//# sourceMappingURL=' + output + '.map';
  }
  await writeFile(output, codeString, 'utf8');

  console.error('done!');

} catch(e) {
  console.error(e.message);
  process.exit(1);
}
