/**
 * Block entry point.
 *
 * Registers the Amitry Product & Category Slider block with the editor.
 *
 * Build pipeline: @wordpress/scripts -> webpack -> build/index.js
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './editor.scss';
import './style.scss';

registerBlockType( metadata.name, {
	edit: Edit,
	// Server-side rendered: no save markup needed.
	save: () => null,
} );
