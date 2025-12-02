/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * WordPress data hooks
 */
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * WordPress components
 */
import { Spinner, Notice } from '@wordpress/components';

/**
 * React hooks
 */
import { useState, useEffect } from '@wordpress/element';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'notiblock-message',
	} );

	const [ settings, setSettings ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( true );

	useEffect( () => {
		// Fetch settings from REST API endpoint.
		apiFetch( { path: '/notiblock/v1/settings' } )
			.then( ( data ) => {
				setSettings( data );
				setIsLoading( false );
			} )
			.catch( () => {
				setSettings( null );
				setIsLoading( false );
			} );
	}, [] );

	if ( isLoading ) {
		return (
			<div { ...blockProps }>
				<Spinner />
			</div>
		);
	}

	if ( ! settings || ! settings.content ) {
		return (
			<div { ...blockProps }>
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'No Notiblock message has been configured in the Dashboard widget yet.',
						'notiblock'
					) }
				</Notice>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<div
				className="notiblock-message__preview"
				dangerouslySetInnerHTML={ { __html: settings.content } }
			/>
		</div>
	);
}

