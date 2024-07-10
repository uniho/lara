
export const css = emotion.css;
export const keyframes = emotion.keyframes;
export const injectGlobal = emotion.injectGlobal;
export const cx = emotion.cx;

export const Styled = (tag, style = {}, options = {}) => styled(tag, options)(style);

export const styled = (tag, options) => (style, ...values) => React.forwardRef((props, ref) => {
  const makeClassName = (style, ...values) =>
    typeof style == 'function' ? makeClassName(style(props)) : emotion.css(style, ...values);
 
  const {sx, ...wosx} = props;

  Object.keys(wosx).forEach(key => {
    if (options && options.shouldForwardProp && !options.shouldForwardProp(key)) {
      delete wosx[key];
    }
  });

  const newProps = {
    ref,
    ...wosx,
    className: emotion.cx(props.className, makeClassName(style, ...values), makeClassName(sx)),
  };

  return React.createElement(tag, newProps, props.children);
});
