<?php
/**
 * Управление разделами — /moderator/sections
 */

// TODO: Проверка авторизации (шаг 11)

render_page('Управление разделами', breadcrumbs_generate([
    ['label' => 'Модерация', 'url' => '/moderator/list'],
    ['label' => 'Разделы', 'url' => null],
]), function () {
    ?>
    <h2>Управление разделами</h2>

    <!-- TODO: Фильтр по имени, дерево разделов, кнопки добавить/редактировать/удалить (шаг 14) -->

    <div class="alert alert-info">
        Управление разделами будет реализовано на шаге 14.
    </div>

    <p>
        <a href="/moderator/list" class="btn btn-outline-secondary">← Назад к списку</a>
    </p>
    <?php
});
