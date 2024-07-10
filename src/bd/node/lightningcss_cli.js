
import {parseArgs} from "node:util";
import {writeFile, readFile} from 'node:fs/promises';
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
    },
    allowPositionals: true,
  });

  const input = args.positionals[0];
  if (!input) {
    console.error('Usage: <in file name> [--outfile=<out file name>] [--minify]');
    process.exit(1);
  }

  const output = args.values.outfile;
  const src = await readFile(input, 'utf8');

  // const palettes = {
  //   '--color1': '#888888',
  // };

  const getValue = arg => {
    // console.log(arg)
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
          default:
            throw new Error('unknown token'); 
        } 
    }  
    
    // 
    return arg.value.value;
  };

  const declared = new Map();
  
  let { code, map } = transform({
    filename: input,
    code: Buffer.from(src),
    minify: args.values.minify,
    include: Features.Colors | Features.Nesting | Features.MediaQueries,
    sourceMap: true,
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
        lighten(info) {
          const color = getValue(info.arguments[0]);
          const amount = getValue(info.arguments[2]).value;
          if (color.type == 'string') {
            return (
              { raw: `hsl(from ${color.value} h s calc(l + ${amount}%))` }
            );
          } 
          return (
            { raw: `hsl(from rgb(${color.r} ${color.g} ${color.b}/${color.alpha}) h s calc(l + ${amount}%))` }
          );
        },
        darken(info) {
          const color = getValue(info.arguments[0]);
          const amount = getValue(info.arguments[2]).value;
          if (color.type == 'string') {
            return (
              { raw: `hsl(from ${color.value} h s calc(l - ${amount}%))` }
            );
          } 
          return (
            { raw: `hsl(from rgb(${color.r} ${color.g} ${color.b}/${color.alpha}) h s calc(l - ${amount}%))` }
          );
        },
      },
    },
  });
  
  if (!output) {
    console.log(code.toString());
    console.error('done!');
    process.exit(0);
  }

  await writeFile(output, code.toString() + '\n//# sourceMappingURL='+output+'.map', 'utf8');
  await writeFile(output+'.map', map.toString(), 'utf8');

  console.error('done!');

} catch(e) {
  console.error(e.message);
  process.exit(1);
}
