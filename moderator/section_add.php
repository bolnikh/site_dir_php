<?php
/**
 * Добавление раздела — /moderator/sections/add
 */

require_moderator();

render_page('Добавить раздел', breadcrumbs_generate([
    ['label' => 'Модерация', 'url' => '/moderator/list'],
    ['label' => 'Разделы', 'url' => '/moderator/sections'],
    ['label' => 'Добавить', 'url' => null],
]), function () {
    ?>
    <h2>Добавить раздел</h2>

    <!-- TODO: Форма добавления раздела (шаг 14) -->

    <div class="alert alert-info">
        Добавление раздела будет реализовано на шаге 14.
    </div>

    <p>
        <a href="/moderator/sections" class="btn btn-outline-secondary">← Назад к разделам</a>
    </p>
    <?php
});
