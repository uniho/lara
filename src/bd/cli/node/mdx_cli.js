import {parseArgs} from "node:util";
import {writeFile, readFile} from 'node:fs/promises';
import process from 'node:process';
import {compile} from '@mdx-js/mdx';

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

  const removeHeads = (s, n) => s.split('\n').slice(n).join('\n'); // 文字列の先頭行を削除
  const out = removeHeads(String(await compile(src, {jsx: true})), 2);

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
