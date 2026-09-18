/**
 * Stimulus controller registry.
 *
 * Controllers are auto-discovered from this directory tree, so a new
 * controller only needs to be dropped in with a matching
 * `data-controller="<name>"` attribute in the markup.
 *
 * The identifier must be kebab-case: `flash_controller.js` registers as
 * `flash`, and `admin/nav_controller.js` as `admin--nav`.
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
        .replace(/_/g, '-')
        .replace(/\//g, '--');

    application.register(name, module.default);
}

export { application };
