import { useState, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import RichEditor from './RichEditor';

const config = window.notiblockAdmin ?? {};
const today = config.currentDate ?? new Date().toISOString().slice( 0, 10 );

const EDITOR_ID = 'notiblock-message-editor';

/**
 * Returns true if the notification should currently be active, computed
 * client-side using the WordPress-timezone date passed from the server.
 *
 * @param {Object} settings
 * @return {boolean}
 */
function computeIsActive( settings ) {
	if ( settings.always_show ) {
		return true;
	}
	if ( ! settings.start_date && ! settings.end_date ) {
		return false;
	}
	if ( settings.start_date && today < settings.start_date ) {
		return false;
	}
	if ( settings.end_date && today > settings.end_date ) {
		return false;
	}
	return true;
}

/**
 * Builds the human-readable display-period sentence for the status notice.
 *
 * @param {Object} settings
 * @return {string} Description of the configured display window.
 */
function describePeriod( settings ) {
	if ( settings.start_date && settings.end_date ) {
		return sprintf(
			/* translators: 1: start date, 2: end date. */
			__( 'Display period: %1$s to %2$s', 'notiblock' ),
			settings.start_date,
			settings.end_date
		);
	}

	if ( settings.start_date ) {
		return sprintf(
			/* translators: %s: start date. */
			__( 'Display from: %s', 'notiblock' ),
			settings.start_date
		);
	}

	return sprintf(
		/* translators: %s: end date. */
		__( 'Display until: %s', 'notiblock' ),
		settings.end_date
	);
}

/**
 * Notiblock settings form.
 * Reads initial state from window.notiblockAdmin.settings and saves via
 * the REST API endpoint at window.notiblockAdmin.restUrl.
 */
export default function SettingsApp() {
	const editorRef = useRef( null );

	const [ settings, setSettings ] = useState( () => ( {
		content: config.settings?.content ?? '',
		start_date: config.settings?.start_date ?? '',
		end_date: config.settings?.end_date ?? '',
		always_show: config.settings?.always_show ?? false,
	} ) );

	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null ); // { type: 'success'|'error', text: string }

	function setField( field, value ) {
		setSettings( ( prev ) => ( { ...prev, [ field ]: value } ) );
	}

	async function handleSave() {
		setSaving( true );
		setNotice( null );

		try {
			const response = await fetch( config.restUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': config.nonce,
				},
				body: JSON.stringify( settings ),
			} );

			const data = await response.json();

			if ( ! response.ok ) {
				throw new Error( data.message ?? __( 'Failed to save settings.', 'notiblock' ) );
			}

			// Update state with whatever the server returned (sanitized values).
			setSettings( {
				content: data.content ?? '',
				start_date: data.start_date ?? '',
				end_date: data.end_date ?? '',
				always_show: data.always_show ?? false,
			} );

			const message = __( 'Settings saved successfully.', 'notiblock' );
			setNotice( { type: 'success', text: message } );
			speak( message, 'polite' );
		} catch ( err ) {
			setNotice( { type: 'error', text: err.message } );
			speak( err.message, 'assertive' );
		} finally {
			setSaving( false );
		}
	}

	const isActive = computeIsActive( settings );
	const hasContent = settings.content.trim().length > 0;

	return (
		<div className="notiblock-settings">
			{ config.isNetworkWide && (
				<div className="notice notice-info inline">
					<p>
						<strong>{ __( 'Network-wide Mode:', 'notiblock' ) }</strong>{ ' ' }
						{ __(
							'These settings apply to all sites in the network.',
							'notiblock'
						) }
					</p>
				</div>
			) }

			{ notice && (
				<div className={ `notice notice-${ notice.type } inline` }>
					<p>{ notice.text }</p>
				</div>
			) }

			<div className="notiblock-settings__field">
				<label htmlFor={ EDITOR_ID }>
					<strong>{ __( 'Notification Message:', 'notiblock' ) }</strong>
				</label>
				<RichEditor
					id={ EDITOR_ID }
					placeholder={ __( 'Enter notification message…', 'notiblock' ) }
					initialContent={ config.settings?.content ?? '' }
					editorRef={ editorRef }
					onChange={ ( html ) => setField( 'content', html ) }
				/>
			</div>

			<div
				className={ `notiblock-settings__field${
					settings.always_show ? ' notiblock-settings__field--disabled' : ''
				}` }
			>
				<label htmlFor="nb-start-date">
					<strong>{ __( 'Start Date:', 'notiblock' ) }</strong>
				</label>
				<br />
				<input
					id="nb-start-date"
					type="date"
					value={ settings.start_date }
					min={ today }
					disabled={ settings.always_show }
					onChange={ ( e ) => {
						const newStart = e.target.value;
						// Clear end date if it's now before the new start date.
						if ( settings.end_date && newStart && settings.end_date < newStart ) {
							setSettings( ( prev ) => ( {
								...prev,
								start_date: newStart,
								end_date: '',
							} ) );
						} else {
							setField( 'start_date', newStart );
						}
					} }
					className="regular-text"
				/>
				<p className="description">
					{ __( 'Leave empty for no start date restriction.', 'notiblock' ) }
				</p>
			</div>

			<div
				className={ `notiblock-settings__field${
					settings.always_show ? ' notiblock-settings__field--disabled' : ''
				}` }
			>
				<label htmlFor="nb-end-date">
					<strong>{ __( 'End Date:', 'notiblock' ) }</strong>
				</label>
				<br />
				<input
					id="nb-end-date"
					type="date"
					value={ settings.end_date }
					min={ settings.start_date || today }
					disabled={ settings.always_show }
					onChange={ ( e ) => setField( 'end_date', e.target.value ) }
					className="regular-text"
				/>
				<p className="description">
					{ __( 'Leave empty for no end date restriction.', 'notiblock' ) }
				</p>
			</div>

			<div className="notiblock-settings__field">
				<label htmlFor="nb-always-show">
					<input
						id="nb-always-show"
						type="checkbox"
						checked={ settings.always_show }
						onChange={ ( e ) => setField( 'always_show', e.target.checked ) }
					/>
					{ ' ' }
					<strong>{ __( 'Always show (ignore date range)', 'notiblock' ) }</strong>
				</label>
			</div>

			{ hasContent && (
				<div
					className={ `notice ${
						isActive ? 'notice-success' : 'notice-warning'
					} inline notiblock-settings__status` }
				>
					<p>
						<strong>{ __( 'Current Status:', 'notiblock' ) }</strong>{ ' ' }
						{ isActive ? __( 'Active', 'notiblock' ) : __( 'Inactive', 'notiblock' ) }
						{ ! settings.always_show &&
							( settings.start_date || settings.end_date ) && (
							<>
								{ ' — ' }
								{ describePeriod( settings ) }
							</>
						) }
						{ settings.always_show &&
							` — ${ __(
								'Always visible (date range ignored)',
								'notiblock'
							) }` }
					</p>
				</div>
			) }

			<div className="notiblock-settings__actions">
				<button
					className="button button-primary"
					onClick={ handleSave }
					disabled={ saving }
					type="button"
				>
					{ saving ? __( 'Saving…', 'notiblock' ) : __( 'Save Settings', 'notiblock' ) }
				</button>
			</div>
		</div>
	);
}
