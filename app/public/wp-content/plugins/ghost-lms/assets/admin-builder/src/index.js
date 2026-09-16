/**
 * Entry point for the Ghost LMS course builder.
 */
import { createRoot } from '@wordpress/element';
import App from './App';

document.addEventListener('DOMContentLoaded', () => {
    const rootElement = document.getElementById('glms-course-builder-root');
    if (rootElement && window.glmsBuilder) {
        const root = createRoot(rootElement);
        root.render(<App courseId={window.glmsBuilder.courseId} />);
    }
});
