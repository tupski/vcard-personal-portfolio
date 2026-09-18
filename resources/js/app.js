/**
 * Artupski Portfolio CMS - application entry point.
 *
 * Order matters: Turbo must be started before Stimulus so that any
 * controller attached to the initial document is picked up on boot.
 */
import '@hotwired/turbo';
import './turbo';
import './controllers';
