
@css
// <style>

.body {
  // lightnig CSS でトランスパイルされます
  color: red;
  .blue {
    color: blue;
    background: green;
  }
}

// </style>
@endcss

export default `@stackcss(["no-style-tag" => true])`;
