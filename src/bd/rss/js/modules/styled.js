
export const css = emotion.css;
export const keyframes = emotion.keyframes;
export const injectGlobal = emotion.injectGlobal;
export const cx = emotion.cx;

export const Styled = (tag, style = {}, options = {}) => styled(tag, options)(style);

export const styled = (tag, options) => (style, ...values) => props => {
  const makeClassName = (style, ...values) =>
    typeof style == 'function' ? makeClassName(style(props)) : emotion.css(style, ...values);
 
  const {sx, className, 'class': _class, children, ...wosx} = props;

  Object.keys(wosx).forEach(key => {
    if (options && options.shouldForwardProp && !options.shouldForwardProp(key)) {
      delete wosx[key];
    }
  });

  const newProps = {
    ...wosx,
    className: emotion.cx(makeClassName(style, ...values), makeClassName(sx), _class, className),
  };

  return React.createElement(tag, newProps, children);
};
