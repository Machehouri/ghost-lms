import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType('ghost-lms/course-catalog', {
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Course Catalog Settings', 'ghost-lms')}>
                        <RangeControl
                            label={__('Posts per page', 'ghost-lms')}
                            value={attributes.postsPerPage || 12}
                            min={1}
                            max={24}
                            onChange={(value) => setAttributes({ postsPerPage: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps}>
                    <p>{__('Ghost LMS Course Catalog', 'ghost-lms')}</p>
                    <p>{__('Published courses will appear here on the front end.', 'ghost-lms')}</p>
                </div>
            </>
        );
    },
    save: () => null,
});
