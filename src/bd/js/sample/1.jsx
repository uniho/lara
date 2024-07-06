import {Styled, styled, css} from 'modules/styled.js'
import {Sx} from 'modules/styled-plus.js'

//
export const main = async props => {
  return {page1: 'ok!'};
}

//
export default props => {
  return (
    <Sx.flexCol sx={{margin: '.5rem 1rem'}}>
      {/* コメント*/}
      <div>
        Welcome to World of React on Blade!
      </div>
      <Inner>
        <Sx.button filled="1">
          Contained
        </Sx.button>
        <Div1>
          Div!Div!
        </Div1>
        <Span1 my-color="skyblue">
          Span!Span!
        </Span1>
      </Inner>
    </Sx.flexCol>
  );
}

// Define useful Sx Components
window.Sx = {
  div: styled('div')({}),
  span: styled('span')({}),
  flex: styled('div')({display:'flex'}),
  flexCol: styled('div')({display:'flex', flexDirection:'column'}),
};

//
const Inner = styled(Sx.flex)`
  padding-top: 2rem;
  align-items: center;
`;

//
const Div1 = styled('div')({
  marginLeft: '2rem',
});

//
const Span1 = styled('span')(props => {
  return css`
    color: ${props['my-color']};
    margin-left: 2rem;
  `;
});
