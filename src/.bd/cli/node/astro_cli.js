// USAGE:
//   cd <Astro project root dir> && node astro_cli.js <props json> </[page name]>
//     OR
//   node astro_cli.js <props json> </[page name]> <Astro project root dir>

import { execSync } from "node:child_process";
import fs from "node:fs";

const props = process.argv[2];
const page = process.argv[3];
const astroRootPath = process.argv[4];

if (astroRootPath) process.chdir(astroRootPath);

execSync("npm run build", {
  stdio: ["ignore", "ignore", "inherit"], // stdout 無視、stderr のみ表示
  env: {
    ...process.env,
    ASTRO_PROPS: props,
  },
});

const html = fs.readFileSync(`dist${page}.html`, "utf-8");

process.stdout.write(html);
