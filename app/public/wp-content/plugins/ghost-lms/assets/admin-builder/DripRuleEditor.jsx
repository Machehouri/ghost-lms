import { Button, PanelBody, RadioControl, SelectControl, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

const RULE_TYPES = [
    { label: 'No drip rule', value: 'none' },
    { label: 'Fixed date', value: 'fixed_date' },
    { label: 'Days after enrollment', value: 'days_after_enrollment' },
    { label: 'Prerequisite lesson', value: 'prerequisite' },
];

const BADGE_LABELS = {
    none: 'No drip rule',
    fixed_date: 'Fixed date drip rule',
    days_after_enrollment: 'Enrollment delay drip rule',
    prerequisite: 'Prerequisite drip rule',
};

export function getDripBadge(rule) {
    const type = rule?.type || 'none';
    return {
        label: BADGE_LABELS[type] || BADGE_LABELS.none,
        icon: { none: '○', fixed_date: '◷', days_after_enrollment: '◴', prerequisite: '↗' }[type] || '○',
        type,
    };
}

export default function DripRuleEditor({ lesson, lessons, apiRoot, nonce, onSaved }) {
    const savedRule = lesson.dripRule || null;
    const [isOpen, setIsOpen] = useState(false);
    const [type, setType] = useState(savedRule?.type || 'none');
    const [date, setDate] = useState(savedRule?.date || '');
    const [days, setDays] = useState(String(savedRule?.days || 1));
    const [prerequisiteLesson, setPrerequisiteLesson] = useState(String(savedRule?.lesson_id || ''));
    const [error, setError] = useState('');
    const [isSaving, setIsSaving] = useState(false);

    const badge = getDripBadge(savedRule);
    const sameCourseLessons = lessons.filter(
        (candidate) => candidate.lesson_id !== lesson.lesson_id && candidate.index < lesson.index,
    );

    function buildRule() {
        if (type === 'none') {
            return null;
        }
        if (type === 'fixed_date') {
            return { type, date: /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(date) ? `${date}:00` : date };
        }
        if (type === 'days_after_enrollment') {
            return { type, days: Number(days) };
        }
        return { type, lesson_id: Number(prerequisiteLesson) };
    }

    async function saveRule() {
        const previousRule = lesson.dripRule;
        const nextRule = buildRule();
        setIsSaving(true);
        setError('');
        lesson.dripRule = nextRule;
        try {
            const response = await fetch(`${apiRoot}/ghost-lms/v1/lessons/${lesson.lesson_id}/drip-rule`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                body: JSON.stringify(nextRule),
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'The drip rule could not be saved.');
            }
            lesson.dripRule = result.rule?.config || null;
            onSaved(lesson);
            setIsOpen(false);
        } catch (saveError) {
            lesson.dripRule = previousRule;
            setError(saveError.message);
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <div className="glms-drip-rule-editor">
            <Button
                className={`glms-drip-rule-editor__badge is-${badge.type}`}
                label={badge.label}
                onClick={() => setIsOpen(!isOpen)}
                aria-expanded={isOpen}
            >
                <span aria-hidden="true">{badge.icon}</span>
            </Button>
            {isOpen && (
                <PanelBody title="Drip rule" initialOpen={true} className="glms-drip-rule-editor__panel">
                    <RadioControl label="Unlock rule" selected={type} options={RULE_TYPES} onChange={setType} />
                    {type === 'fixed_date' && (
                        <TextControl label="Unlock date and time" type="datetime-local" value={date} onChange={setDate} />
                    )}
                    {type === 'days_after_enrollment' && (
                        <TextControl label="Days after enrollment" type="number" min="1" value={days} onChange={setDays} />
                    )}
                    {type === 'prerequisite' && (
                        <SelectControl
                            label="Required lesson"
                            value={prerequisiteLesson}
                            options={[{ label: 'Select a lesson', value: '' }, ...sameCourseLessons.map((candidate) => ({ label: candidate.title, value: String(candidate.lesson_id) }))]}
                            onChange={setPrerequisiteLesson}
                        />
                    )}
                    {error && <p className="glms-drip-rule-editor__error" role="alert">{error}</p>}
                    <Button variant="primary" onClick={saveRule} disabled={isSaving}>
                        {isSaving ? 'Saving...' : 'Save drip rule'}
                    </Button>
                </PanelBody>
            )}
        </div>
    );
}
