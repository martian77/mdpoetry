import { registerBlockType } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: () => <ServerSideRender block={ metadata.name } />,
	save: () => null,
} );
