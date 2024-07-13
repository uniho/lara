import {parseArgs} from "node:util";
import {writeFile, readFile} from 'node:fs/promises';
import process from 'node:process';
import Handlebars from "handlebars";

try {
  const args = parseArgs({
    options: {
      outfile: {
        type: "string",
        default: "",
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
    // console.error('Usage: <in file name> [--outfile=<out file name>]');
  }

  const pattern =
    '^(' +
    '(= yaml =|---)' +
    '$([\\s\\S]*?)' +
    '^(?:\\2|\\.\\.\\.)\\s*' +
    '$' +
    '\\r?' +
    '(?:\\n)?)';

  const regex = new RegExp(pattern, 'm')
  const m = regex.exec(src);
  let frontmatter = {};
  let body = src;
  if (m) {
    frontmatter = JSON.parse(m[m.length - 1].replace(/^\s+|\s+$/g, ''));
    body = src.replace(m[0], '');
  } 

  const template = Handlebars.compile(body);
  const out = template(frontmatter);
  
  if (!output) {
    console.log(out);
    console.error('done!');
    process.exit(0);
  }

  await writeFile(output, out, 'utf8');
  // await writeFile(output+'.map', map.toString(), 'utf8');

  console.error('done!');

} catch(e) {
  console.error(e.message);
  process.exit(1);
}
