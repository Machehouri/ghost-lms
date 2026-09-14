import DripRuleEditor, { getDripBadge } from './DripRuleEditor';

export default function LessonList({ lessons, apiRoot, nonce, onLessonSaved }) {
    return (
        <ul className="glms-lesson-list">
            {lessons.map((lesson) => {
                const badge = getDripBadge(lesson.dripRule);
                return (
                    <li className="glms-lesson-list__item" key={lesson.lesson_id}>
                        <span className="glms-lesson-list__title">{lesson.title}</span>
                        <span className={`glms-lesson-list__status is-${badge.type}`} title={badge.label} aria-label={badge.label}>
                            <span aria-hidden="true">{badge.icon}</span>
                        </span>
                        <DripRuleEditor
                            lesson={lesson}
                            lessons={lessons}
                            apiRoot={apiRoot}
                            nonce={nonce}
                            onSaved={onLessonSaved}
                        />
                    </li>
                );
            })}
        </ul>
    );
}
