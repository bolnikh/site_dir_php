<?php
/**
 * Список сайтов на модерации — /moderator/list
 */

require_moderator();

render_page('Сайты на модерации', breadcrumbs_generate([
    ['label' => 'Модерация', 'url' => null],
]), function () {
    ?>
    <h2>Сайты на модерации</h2>

    <!-- TODO: Список сайтов на модерации (шаг 12) -->

    <div class="alert alert-info">
        Список сайтов на модерации будет реализован на шаге 12.
    </div>

    <p>
        <a href="/moderator/sections" class="btn btn-outline-secondary">Управление разделами</a>
    </p>
    <?php
});
