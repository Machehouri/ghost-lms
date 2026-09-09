import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType('ghost-lms/course-detail', {
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Course Detail Settings', 'ghost-lms')}>
                        <TextControl
                            label={__('Course ID', 'ghost-lms')}
                            type="number"
                            value={attributes.courseId || 0}
                            onChange={(value) => setAttributes({ courseId: Number(value || 0) })}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps}>
                    <p>{__('Ghost LMS Course Detail', 'ghost-lms')}</p>
                    <p>{__('The course details will render on the front end.', 'ghost-lms')}</p>
                </div>
            </>
        );
    },
    save: () => null,
});
