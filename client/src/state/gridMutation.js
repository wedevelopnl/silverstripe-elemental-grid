import { graphql } from '@apollo/client/react/hoc';
import { gql } from '@apollo/client';

const mutation = gql`
  mutation UpdateElementGrid($id: ID!, $sizeLG: Int, $offsetLG: Int) {
    updateElementGrid(id: $id, sizeLG: $sizeLG, offsetLG: $offsetLG) {
      id
      sizeLG
      offsetLG
    }
  }
`;

const config = {
  props: ({ mutate, ownProps: { actions } }) => {
    const handleUpdateGrid = (elementId, gridData) => mutate({
      variables: {
        id: elementId,
        ...gridData,
      },
      optimisticResponse: {
        updateElementGrid: {
          id: elementId,
          sizeLG: gridData.sizeLG,
          offsetLG: gridData.offsetLG,
          __typename: 'BaseElement',
        },
      },
    });

    return {
      actions: {
        ...actions,
        updateElementGrid: handleUpdateGrid,
      },
    };
  },
};

export { mutation, config };

export default graphql(mutation, config);
