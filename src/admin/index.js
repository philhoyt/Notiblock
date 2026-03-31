import { createRoot } from '@wordpress/element';
import SettingsApp from './SettingsApp';
import './style.scss';

// Mount in the dashboard widget.
const widgetEl = document.getElementById( 'notiblock-widget-root' );
if ( widgetEl ) {
	createRoot( widgetEl ).render( <SettingsApp /> );
}

// Mount on the dedicated settings page.
const settingsEl = document.getElementById( 'notiblock-settings-root' );
if ( settingsEl ) {
	createRoot( settingsEl ).render( <SettingsApp /> );
}
