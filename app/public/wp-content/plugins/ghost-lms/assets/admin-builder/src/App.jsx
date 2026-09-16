/**
 * Course builder app shell.
 */
import { useState, useEffect } from '@wordpress/element';
import { apiFetch } from '@wordpress/api-fetch';

apiFetch.use(apiFetch.createNonceMiddleware(window.glmsBuilder?.nonce));

export default function App({ courseId }) {
    const [course, setCourse] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        async function fetchCourse() {
            try {
                setIsLoading(true);
                const data = await apiFetch({
                    path: `/wp/v2/glms_course/${courseId}`,
                });
                setCourse(data);
            } catch (err) {
                setError(err.message || 'Failed to load course');
            } finally {
                setIsLoading(false);
            }
        }

        if (courseId) {
            fetchCourse();
        }
    }, [courseId]);

    if (isLoading) {
        return (
            <div style={{ padding: '40px', textAlign: 'center' }}>
                <p>Loading course...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div style={{ padding: '40px' }}>
                <div style={{ padding: '16px', background: '#f6d7d7', borderLeft: '4px solid #dc3232', marginBottom: '16px' }}>
                    <strong>Error:</strong> {error}
                </div>
            </div>
        );
    }

    return (
        <div style={{ padding: '20px' }}>
            <h1 style={{ marginBottom: '20px' }}>
                Course Builder: {course?.title?.rendered || 'Untitled Course'}
            </h1>
            <div style={{
                padding: '40px',
                textAlign: 'center',
                background: '#f9f9f9',
                borderRadius: '4px',
                border: '1px dashed #ddd'
            }}>
                <h2 style={{ marginBottom: '16px' }}>No modules yet</h2>
                <p style={{ marginBottom: '16px', color: '#666' }}>
                    Add your first module to start building your course curriculum.
                </p>
                <button
                    type="button"
                    className="button button-primary"
                    disabled
                    style={{ opacity: '0.5', cursor: 'not-allowed' }}
                >
                    Add Module
                </button>
            </div>
        </div>
    );
}
