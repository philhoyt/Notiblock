import { useState, useRef } from '@wordpress/element';
import RichEditor from './RichEditor';

const config = window.notiblockAdmin ?? {};

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
	const today = config.currentDate ?? new Date().toISOString().slice( 0, 10 );
	if ( settings.start_date && today < settings.start_date ) {
		return false;
	}
	if ( settings.end_date && today > settings.end_date ) {
		return false;
	}
	return true;
}

/**
 * Notiblock settings form.
 * Reads initial state from window.notiblockAdmin.settings and saves via
 * the REST API endpoint at window.notiblockAdmin.restUrl.
 */
export default function SettingsApp() {
	const editorRef = useRef( null );

	const [ settings, setSettings ] = useState( () => ( {
		content:     config.settings?.content     ?? '',
		start_date:  config.settings?.start_date  ?? '',
		end_date:    config.settings?.end_date     ?? '',
		always_show: config.settings?.always_show ?? false,
	} ) );

	const [ saving,  setSaving  ] = useState( false );
	const [ notice,  setNotice  ] = useState( null ); // { type: 'success'|'error', text: string }

	function setField( field, value ) {
		setSettings( ( prev ) => ( { ...prev, [ field ]: value } ) );
	}

	async function handleSave() {
		setSaving( true );
		setNotice( null );

		try {
			const response = await fetch( config.restUrl, {
				method:  'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce':   config.nonce,
				},
				body: JSON.stringify( settings ),
			} );

			const data = await response.json();

			if ( ! response.ok ) {
				throw new Error( data.message ?? 'Failed to save settings.' );
			}

			// Update state with whatever the server returned (sanitized values).
			setSettings( {
				content:     data.content     ?? '',
				start_date:  data.start_date  ?? '',
				end_date:    data.end_date     ?? '',
				always_show: data.always_show ?? false,
			} );
			setNotice( { type: 'success', text: 'Settings saved successfully.' } );
		} catch ( err ) {
			setNotice( { type: 'error', text: err.message } );
		} finally {
			setSaving( false );
		}
	}

	const isActive   = computeIsActive( settings );
	const hasContent = settings.content.trim().length > 0;

	return (
		<div className="notiblock-settings">
			{ config.isNetworkWide && (
				<div className="notice notice-info inline">
					<p>
						<strong>Network-wide Mode:</strong>{ ' ' }
						These settings apply to all sites in the network.
					</p>
				</div>
			) }

			{ notice && (
				<div className={ `notice notice-${ notice.type } inline is-dismissible` }>
					<p>{ notice.text }</p>
				</div>
			) }

			<div className="notiblock-settings__field">
				<label>
					<strong>Notification Message:</strong>
				</label>
				<RichEditor
					placeholder="Enter notification message…"
					initialContent={ config.settings?.content ?? '' }
					editorRef={ editorRef }
					onChange={ ( html ) => setField( 'content', html ) }
				/>
			</div>

			<div className="notiblock-settings__field">
				<label htmlFor="nb-start-date">
					<strong>Start Date:</strong>
				</label>
				<br />
				<input
					id="nb-start-date"
					type="date"
					value={ settings.start_date }
					onChange={ ( e ) => setField( 'start_date', e.target.value ) }
					className="regular-text"
				/>
				<p className="description">Leave empty for no start date restriction.</p>
			</div>

			<div className="notiblock-settings__field">
				<label htmlFor="nb-end-date">
					<strong>End Date:</strong>
				</label>
				<br />
				<input
					id="nb-end-date"
					type="date"
					value={ settings.end_date }
					onChange={ ( e ) => setField( 'end_date', e.target.value ) }
					className="regular-text"
				/>
				<p className="description">Leave empty for no end date restriction.</p>
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
					<strong>Always show (ignore date range)</strong>
				</label>
			</div>

			{ hasContent && (
				<div
					className={ `notice ${ isActive ? 'notice-success' : 'notice-warning' } inline notiblock-settings__status` }
				>
					<p>
						<strong>Current Status:</strong>{ ' ' }
						{ isActive ? 'Active' : 'Inactive' }
						{ ! settings.always_show && ( settings.start_date || settings.end_date ) && (
							<>
								{ ' — ' }
								{ settings.start_date && settings.end_date
									? `Display period: ${ settings.start_date } to ${ settings.end_date }`
									: settings.start_date
										? `Display from: ${ settings.start_date }`
										: `Display until: ${ settings.end_date }`
								}
							</>
						) }
						{ settings.always_show && ' — Always visible (date range ignored)' }
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
					{ saving ? 'Saving…' : 'Save Settings' }
				</button>
			</div>
		</div>
	);
}
