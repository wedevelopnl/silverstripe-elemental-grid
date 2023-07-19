import { graphql } from '@apollo/client/react/hoc';
import gql from 'graphql-tag';
import { query as readBlocksQuery, config as readBlocksConfig } from './readBlocksForAreaQuery';

// GraphQL query for deleting a specific block
const mutation = gql`
mutation AddElementToArea($className: String!, $elementalAreaID: ID!, $afterElementID: ID, $insertAtBottom: Boolean) {
  addElementToArea(
    className: $className,
    elementalAreaID: $elementalAreaID,
    afterElementID: $afterElementID,
    insertAtBottom: $insertAtBottom
  ) {
    id
  }
}
`;

const config = {
  props: ({ mutate, ownProps: { actions, areaId } }) => {
    const handleAddElementToArea = (className, afterElementID, insertAtBottom) => mutate({
      variables: { className, elementalAreaID: areaId, afterElementID, insertAtBottom },
    });

    return {
      actions: {
        ...actions,
        handleAddElementToArea,
      },
    };
  },
  options: ({ areaId }) => ({
    // Refetch versions after mutation is completed
    refetchQueries: [{
      query: readBlocksQuery,
      variables: readBlocksConfig.options({ areaId }).variables
    }]
  })
};

export { mutation, config };

export default graphql(mutation, config);
