import React from "https://esm.sh/react@canary?dev";
import ReactDOM from "https://esm.sh/react-dom@canary?dev";
import ReactDOMClient from "https://esm.sh/react-dom@canary/client?dev";
// import ReactDOMServer from "https://esm.sh/react-dom@canary/server.browser?dev";
import htm from "https://esm.sh/htm";
import * as emotion from "https://esm.sh/@emotion/css@11";

window.React = React;
window.ReactDOM = ReactDOM;
window.ReactDOMClient = ReactDOMClient;
// window.ReactDOMServer = ReactDOMServer;
window.htm = htm;
window.html = htm.bind(React.createElement)
window.emotion = emotion;

window.Fragment = React.Fragment;
window.Suspense = React.Suspense;

//
export const main = async props => {
  const page = await import(`jsx/sample/${props.page ?? 1}.jsx`);
  const newProps = Object.assign(props, page.main ? await page.main(props) : {});
  const Page = page.default;
  const root = ReactDOMClient.createRoot(document.getElementById("app"));
  root.render(React.createElement(Page, newProps));
}
