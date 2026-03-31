import { useState, useEffect } from '@wordpress/element';
import { create, toHTMLString } from '@wordpress/rich-text';

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

/**
 * Walks up the DOM from the current selection anchor to find the nearest
 * inline text-align style, stopping at the editor boundary.
 * More reliable than document.queryCommandValue('justify') which returns
 * 'left' as a default even when no alignment has been applied.
 *
 * @param {Selection} sel        Current document selection.
 * @param {Element}   editorEl   The contenteditable root element.
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

const AlignRightIcon = () => (
	<svg width="14" height="12" viewBox="0 0 14 12" fill="currentColor" aria-hidden="true">
		<rect x="0" y="0" width="14" height="2" />
		<rect x="5" y="4" width="9" height="2" />
		<rect x="0" y="8" width="14" height="2" />
	</svg>
);

/**
 * Lightweight contenteditable rich text editor.
 * Uses @wordpress/rich-text for HTML normalization on read.
 * Uses document.execCommand for format toggling.
 *
 * @param {Object}          root0
 * @param {string}          root0.placeholder
 * @param {string}          root0.initialContent  HTML string to pre-fill on mount.
 * @param {React.RefObject} root0.editorRef        Forwarded ref to the contenteditable div.
 * @param {Function}        root0.onChange         Called with normalized HTML on every edit.
 */
export default function RichEditor( { placeholder, initialContent = '', editorRef, onChange } ) {
	const [ isEmpty, setIsEmpty ] = useState( ! initialContent );
	const [ currentAlign, setCurrentAlign ] = useState( 'left' );

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

	function handleLink() {
		// eslint-disable-next-line no-alert
		const url = window.prompt( 'Enter URL:' );
		if ( url ) {
			editorRef.current?.focus();
			document.execCommand( 'createLink', false, url );
			handleInput();
		}
	}

	return (
		<div className="nb-rich-editor">
			<div
				className="nb-rich-editor__toolbar"
				role="toolbar"
				aria-label="Formatting"
			>
				<ToolbarButton
					label="Bold"
					onMouseDown={ () => execFormat( 'bold' ) }
				>
					<strong>B</strong>
				</ToolbarButton>
				<ToolbarButton
					label="Italic"
					onMouseDown={ () => execFormat( 'italic' ) }
				>
					<em>I</em>
				</ToolbarButton>
				<ToolbarButton label="Link" onMouseDown={ handleLink }>
					&#128279;
				</ToolbarButton>

				<span className="nb-rich-editor__toolbar-sep" aria-hidden="true" />

				<ToolbarButton
					label="Align Left"
					isActive={ currentAlign === 'left' }
					onMouseDown={ () => execAlign( 'justifyLeft', 'left' ) }
				>
					<AlignLeftIcon />
				</ToolbarButton>
				<ToolbarButton
					label="Align Center"
					isActive={ currentAlign === 'center' }
					onMouseDown={ () => execAlign( 'justifyCenter', 'center' ) }
				>
					<AlignCenterIcon />
				</ToolbarButton>
				<ToolbarButton
					label="Align Right"
					isActive={ currentAlign === 'right' }
					onMouseDown={ () => execAlign( 'justifyRight', 'right' ) }
				>
					<AlignRightIcon />
				</ToolbarButton>
			</div>

			<div
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
				aria-label="Notification message"
				aria-placeholder={ placeholder }
			/>
		</div>
	);
}
