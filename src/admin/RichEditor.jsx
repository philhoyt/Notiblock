import { useState, useEffect, useRef } from '@wordpress/element';
import { create, toHTMLString } from '@wordpress/rich-text';
import { Modal, TextControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Protocols permitted in editor links. Anything else — notably `javascript:` —
 * is rejected before it reaches the document. The saved value is also filtered
 * server-side by wp_kses_post(); this is the client-side half of that.
 *
 * @param {string} url Candidate URL.
 * @return {boolean} True when the URL is safe to apply.
 */
export function isAllowedUrl( url ) {
	const trimmed = url.trim();

	if ( ! trimmed ) {
		return false;
	}

	// Relative paths, anchors, and protocol-relative URLs are safe.
	if ( /^(#|\/|\.\/|\.\.\/)/.test( trimmed ) ) {
		return true;
	}

	return /^(https?|mailto|tel):/i.test( trimmed );
}

/**
 * Minimal rich-text toolbar button.
 *
 * @param {Object}   root0
 * @param {string}   root0.label
 * @param {boolean}  root0.isActive
 * @param {Function} root0.onMouseDown
 * @param {*}        root0.children
 */
function ToolbarButton( { label, isActive = false, onMouseDown, children } ) {
	return (
		<button
			type="button"
			className={ `nb-rich-editor__toolbar-btn${ isActive ? ' is-active' : '' }` }
			aria-label={ label }
			aria-pressed={ isActive }
			onMouseDown={ ( e ) => {
				// Prevent blur on the contenteditable before the command runs.
				e.preventDefault();
				onMouseDown();
			} }
		>
			{ children }
		</button>
	);
}

const AlignLeftIcon = () => (
	<svg width="14" height="12" viewBox="0 0 14 12" fill="currentColor" aria-hidden="true">
		<rect x="0" y="0" width="14" height="2" />
		<rect x="0" y="4" width="9" height="2" />
		<rect x="0" y="8" width="14" height="2" />
	</svg>
);

const AlignCenterIcon = () => (
	<svg width="14" height="12" viewBox="0 0 14 12" fill="currentColor" aria-hidden="true">
		<rect x="0" y="0" width="14" height="2" />
		<rect x="2.5" y="4" width="9" height="2" />
		<rect x="0" y="8" width="14" height="2" />
	</svg>
);

const AlignRightIcon = () => (
	<svg width="14" height="12" viewBox="0 0 14 12" fill="currentColor" aria-hidden="true">
		<rect x="0" y="0" width="14" height="2" />
		<rect x="5" y="4" width="9" height="2" />
		<rect x="0" y="8" width="14" height="2" />
	</svg>
);

/**
 * Walks up the DOM from the current selection anchor to find the nearest
 * inline text-align style, stopping at the editor boundary.
 * More reliable than document.queryCommandValue('justify') which returns
 * 'left' as a default even when no alignment has been applied.
 *
 * @param {Selection} sel      Current document selection.
 * @param {Element}   editorEl The contenteditable root element.
 * @return {string} 'left' | 'center' | 'right' | 'justify'
 */
function getSelectionAlign( sel, editorEl ) {
	let node = sel.anchorNode;
	if ( node?.nodeType === Node.TEXT_NODE ) {
		node = node.parentElement;
	}
	while ( node && node !== editorEl ) {
		if ( node.style?.textAlign ) {
			return node.style.textAlign;
		}
		node = node.parentElement;
	}
	return 'left';
}

/**
 * Lightweight contenteditable rich text editor.
 * Uses @wordpress/rich-text for HTML normalization on read.
 *
 * @param {Object}          root0
 * @param {string}          root0.placeholder
 * @param {string}          root0.initialContent HTML string to pre-fill on mount.
 * @param {React.RefObject} root0.editorRef      Forwarded ref to the contenteditable div.
 * @param {Function}        root0.onChange       Called with normalized HTML on every edit.
 * @param {string}          root0.id             DOM id, so a visible <label> can point at it.
 */
export default function RichEditor( { placeholder, initialContent = '', editorRef, onChange, id } ) {
	const [ isEmpty, setIsEmpty ] = useState( ! initialContent );
	const [ currentAlign, setCurrentAlign ] = useState( 'left' );
	const [ isLinkModalOpen, setIsLinkModalOpen ] = useState( false );
	const [ linkUrl, setLinkUrl ] = useState( '' );
	const [ linkError, setLinkError ] = useState( null );

	// The selection is lost when focus moves into the modal, so stash it.
	const savedRangeRef = useRef( null );

	// Set initial HTML content once on mount.
	useEffect( () => {
		if ( editorRef.current && initialContent ) {
			editorRef.current.innerHTML = initialContent;
			setIsEmpty( false );
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	// Track alignment as the cursor moves through the content.
	useEffect( () => {
		function onSelectionChange() {
			const sel = document.getSelection();
			if ( ! sel || ! editorRef.current?.contains( sel.anchorNode ) ) {
				return;
			}
			setCurrentAlign( getSelectionAlign( sel, editorRef.current ) );
		}
		document.addEventListener( 'selectionchange', onSelectionChange );
		return () => document.removeEventListener( 'selectionchange', onSelectionChange );
	}, [ editorRef ] );

	function handleInput() {
		const el = editorRef.current;
		if ( ! el ) {
			return;
		}
		const empty = el.innerText.trim() === '';
		setIsEmpty( empty );
		const rawHtml = empty ? '' : el.innerHTML;
		const value = create( { html: rawHtml } );
		onChange( toHTMLString( { value } ) );
	}

	function handleKeyDown( e ) {
		// Prevent Enter from creating <div> wrappers in some browsers.
		if ( e.key === 'Enter' && ! e.shiftKey ) {
			e.preventDefault();
			document.execCommand( 'insertLineBreak' );
		}
	}

	function execFormat( command ) {
		editorRef.current?.focus();
		document.execCommand( command, false );
		handleInput();
	}

	function execAlign( command, align ) {
		editorRef.current?.focus();
		document.execCommand( command, false );
		setCurrentAlign( align );
	}

	function openLinkModal() {
		const sel = document.getSelection();
		savedRangeRef.current =
			sel && sel.rangeCount > 0 && editorRef.current?.contains( sel.anchorNode )
				? sel.getRangeAt( 0 ).cloneRange()
				: null;

		setLinkUrl( '' );
		setLinkError( null );
		setIsLinkModalOpen( true );
	}

	function closeLinkModal() {
		setIsLinkModalOpen( false );
		setLinkError( null );
	}

	function applyLink() {
		if ( ! isAllowedUrl( linkUrl ) ) {
			setLinkError(
				__(
					'Enter a valid URL beginning with http://, https://, mailto:, tel:, / or #.',
					'notiblock'
				)
			);
			return;
		}

		editorRef.current?.focus();

		// Restore the pre-modal selection so the link wraps the right text.
		if ( savedRangeRef.current ) {
			const sel = document.getSelection();
			sel.removeAllRanges();
			sel.addRange( savedRangeRef.current );
		}

		document.execCommand( 'createLink', false, linkUrl.trim() );
		handleInput();
		closeLinkModal();
	}

	return (
		<div className="nb-rich-editor">
			<div
				className="nb-rich-editor__toolbar"
				role="toolbar"
				aria-label={ __( 'Formatting', 'notiblock' ) }
			>
				<ToolbarButton
					label={ __( 'Bold', 'notiblock' ) }
					onMouseDown={ () => execFormat( 'bold' ) }
				>
					<strong>B</strong>
				</ToolbarButton>
				<ToolbarButton
					label={ __( 'Italic', 'notiblock' ) }
					onMouseDown={ () => execFormat( 'italic' ) }
				>
					<em>I</em>
				</ToolbarButton>
				<ToolbarButton
					label={ __( 'Link', 'notiblock' ) }
					onMouseDown={ openLinkModal }
				>
					&#128279;
				</ToolbarButton>

				<span className="nb-rich-editor__toolbar-sep" aria-hidden="true" />

				<ToolbarButton
					label={ __( 'Align left', 'notiblock' ) }
					isActive={ currentAlign === 'left' }
					onMouseDown={ () => execAlign( 'justifyLeft', 'left' ) }
				>
					<AlignLeftIcon />
				</ToolbarButton>
				<ToolbarButton
					label={ __( 'Align center', 'notiblock' ) }
					isActive={ currentAlign === 'center' }
					onMouseDown={ () => execAlign( 'justifyCenter', 'center' ) }
				>
					<AlignCenterIcon />
				</ToolbarButton>
				<ToolbarButton
					label={ __( 'Align right', 'notiblock' ) }
					isActive={ currentAlign === 'right' }
					onMouseDown={ () => execAlign( 'justifyRight', 'right' ) }
				>
					<AlignRightIcon />
				</ToolbarButton>
			</div>

			<div
				id={ id }
				ref={ editorRef }
				contentEditable
				suppressContentEditableWarning
				onInput={ handleInput }
				onKeyDown={ handleKeyDown }
				className="nb-rich-editor__content"
				data-placeholder={ isEmpty ? placeholder : undefined }
				role="textbox"
				tabIndex={ 0 }
				aria-multiline="true"
				aria-placeholder={ placeholder }
			/>

			{ isLinkModalOpen && (
				<Modal
					title={ __( 'Insert link', 'notiblock' ) }
					onRequestClose={ closeLinkModal }
					className="nb-rich-editor__link-modal"
				>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'URL', 'notiblock' ) }
						value={ linkUrl }
						onChange={ ( value ) => {
							setLinkUrl( value );
							setLinkError( null );
						} }
						onKeyDown={ ( e ) => {
							if ( e.key === 'Enter' ) {
								e.preventDefault();
								applyLink();
							}
						} }
						placeholder="https://example.com"
						help={ linkError }
						aria-invalid={ !! linkError }
					/>
					<div className="nb-rich-editor__link-modal-actions">
						<Button variant="tertiary" onClick={ closeLinkModal }>
							{ __( 'Cancel', 'notiblock' ) }
						</Button>
						<Button variant="primary" onClick={ applyLink }>
							{ __( 'Add link', 'notiblock' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}
