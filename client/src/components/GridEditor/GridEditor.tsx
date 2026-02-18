interface GridEditorProps {
  readonly areaId: number;
  readonly pageId: number | null;
}

/**
 * Root component for the grid editor. Mounted by the entwine bridge
 * inside each `.grid-editor__container` element in the CMS.
 *
 * Currently renders a placeholder — will be expanded to render the
 * full element tree with drag-and-drop editing.
 */
export default function GridEditor({ areaId, pageId }: GridEditorProps) {
  return (
    <div className="grid-editor" data-area-id={areaId} data-page-id={pageId ?? undefined}>
      <p>Grid editor for area {areaId}</p>
    </div>
  );
}
