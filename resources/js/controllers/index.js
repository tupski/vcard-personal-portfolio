/**
 * Stimulus controller registry.
 *
 * Controllers are auto-discovered from this directory tree, so a new
 * controller only needs to be dropped in a sub-folder with a matching
 * `data-controller="<name>"` attribute in the markup.
 */
import { Application } from '@hotwired/stimulus';

const application = Application.start();

application.debug = import.meta.env.DEV;
window.Stimulus = application;

const controllers = import.meta.glob('./**/*_controller.js', { eager: true });

for (const [path, module] of Object.entries(controllers)) {
    const name = path
        .replace(/^\.\//, '')
        .replace(/_controller\.js$/, '')
        .replace(/\//g, '--');

    application.register(name, module.default);
}

export { application };
