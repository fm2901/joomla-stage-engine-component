<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_stageengine
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use StageEngine\Domain\Stage;

$pipeline = Stage::ordered();
?>
<style>
  :root {
    --se-bg: linear-gradient(145deg, #f2f6f0 0%, #e6eef6 55%, #fef6e8 100%);
    --se-panel: #ffffff;
    --se-border: #d8dde5;
    --se-primary: #0f4c81;
    --se-accent: #bf3f34;
    --se-text: #1a2533;
    --se-muted: #5d6b7a;
    --se-ok: #2f7d32;
  }
  .se-wrap { max-width: 1200px; margin: 20px auto; color: var(--se-text); font-family: "Segoe UI", Tahoma, sans-serif; }
  .se-shell { background: var(--se-bg); border: 1px solid var(--se-border); border-radius: 14px; padding: 18px; box-shadow: 0 12px 26px rgba(19, 34, 57, .08); }
  .se-top { display: grid; grid-template-columns: 1.2fr .8fr; gap: 12px; margin-bottom: 12px; }
  .se-panel { background: var(--se-panel); border: 1px solid var(--se-border); border-radius: 12px; padding: 14px; }
  .se-title { margin: 0 0 6px; font-size: 26px; color: #15365a; }
  .se-sub { margin: 0; color: var(--se-muted); }
  .se-controls { display: grid; gap: 8px; }
  .se-inline { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
  .se-input, .se-select { border: 1px solid #b7c2ce; border-radius: 8px; padding: 8px 10px; background: #fff; min-height: 38px; }
  .se-btn { border: 0; border-radius: 8px; padding: 8px 12px; background: var(--se-primary); color: #fff; cursor: pointer; min-height: 38px; }
  .se-btn:hover { filter: brightness(0.95); }
  .se-btn-alt { background: #49617a; }
  .se-btn-danger { background: var(--se-accent); }
  .se-chip { display: inline-block; padding: 4px 10px; border-radius: 99px; border: 1px solid #c5d0dc; background: #f7fafc; font-size: 12px; }
  .se-pipeline { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
  .se-stage { padding: 6px 10px; border-radius: 99px; border: 1px solid #cad4df; background: #fff; color: #3f4f62; font-size: 12px; }
  .se-stage.active { background: #113a66; color: #fff; border-color: #113a66; }
  .se-main { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; }
  .se-card h2 { margin-top: 0; margin-bottom: 8px; color: #173f67; }
  .se-note { margin: 0; color: #2f3f50; background: #f4f8fb; border: 1px solid #d7e2ec; border-radius: 10px; padding: 10px; }
  .se-lock { color: var(--se-ok); font-weight: 600; }
  .se-actions form { margin-bottom: 8px; }
  .se-actions .se-input { width: calc(100% - 16px); }
  .se-history { width: 100%; border-collapse: collapse; font-size: 13px; }
  .se-history th, .se-history td { border: 1px solid #d2dae3; padding: 6px; vertical-align: top; }
  .se-history th { background: #f4f7fa; text-align: left; }
  .se-pre { margin: 0; white-space: pre-wrap; word-break: break-word; font-size: 12px; }
  @media (max-width: 900px) {
    .se-top, .se-main { grid-template-columns: 1fr; }
  }
</style>

<div class="se-wrap">
  <div class="se-shell">
    <div class="se-top">
      <div class="se-panel">
        <h1 class="se-title">Stage Engine</h1>
        <p class="se-sub">
          Компания:
          <strong><?= htmlspecialchars($this->company->name, ENT_QUOTES, 'UTF-8'); ?></strong>
          <span class="se-chip">ID: <?= (int) $this->company->id; ?></span>
        </p>
        <div class="se-pipeline">
          <?php foreach ($pipeline as $code) : ?>
            <span class="se-stage<?= $this->resolvedStage === $code ? ' active' : ''; ?>">
              <?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>: <?= htmlspecialchars(Stage::label($code), ENT_QUOTES, 'UTF-8'); ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="se-panel se-controls">
        <form method="get" class="se-inline">
          <input type="hidden" name="option" value="com_stageengine">
          <input type="hidden" name="view" value="dashboard">
          <select class="se-select" name="company_id">
            <?php foreach ($this->companies as $item) : ?>
              <option value="<?= (int) $item['id']; ?>"<?= (int) $item['id'] === (int) $this->company->id ? ' selected' : ''; ?>>
                #<?= (int) $item['id']; ?> <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button class="se-btn se-btn-alt" type="submit">Выбрать</button>
        </form>
        <form method="post" class="se-inline">
          <input type="hidden" name="task" value="create_company">
          <input class="se-input" type="text" name="company_name" placeholder="Новая компания">
          <?= HTMLHelper::_('form.token'); ?>
          <button class="se-btn" type="submit">Добавить</button>
        </form>
      </div>
    </div>

    <div class="se-main">
      <div class="se-panel se-card">
        <h2>Текущая стадия</h2>
        <p><strong><?= htmlspecialchars(Stage::label($this->resolvedStage), ENT_QUOTES, 'UTF-8'); ?></strong> (<?= htmlspecialchars($this->resolvedStage, ENT_QUOTES, 'UTF-8'); ?>)</p>
        <h2>Инструкция</h2>
        <p class="se-note"><?= htmlspecialchars($this->instructions[$this->resolvedStage] ?? 'Инструкция не найдена.', ENT_QUOTES, 'UTF-8'); ?></p>
        <h2>Переходы</h2>
        <?php if ($this->availableTransitions === []) : ?>
          <p>Переходы недоступны.</p>
        <?php else : ?>
          <?php foreach ($this->availableTransitions as $transition) : ?>
            <form method="post">
              <input type="hidden" name="task" value="transition">
              <input type="hidden" name="target_stage" value="<?= htmlspecialchars($transition['target'], ENT_QUOTES, 'UTF-8'); ?>">
              <?= HTMLHelper::_('form.token'); ?>
              <button class="se-btn" type="submit"><?= htmlspecialchars($transition['label'], ENT_QUOTES, 'UTF-8'); ?></button>
            </form>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="se-panel se-card se-actions">
        <h2>Действия</h2>
        <?php if ($this->isActivated) : ?>
          <p class="se-lock">Компания достигла Activated. Дальнейшие pipeline-действия отключены.</p>
        <?php else : ?>
          <form method="post">
            <input type="hidden" name="task" value="attempt_contact">
            <input class="se-input" type="text" name="channel" value="call" placeholder="channel">
            <?= HTMLHelper::_('form.token'); ?>
            <button class="se-btn" type="submit">Попытка контакта</button>
          </form>
          <form method="post">
            <input type="hidden" name="task" value="decision_maker_call">
            <input class="se-input" type="text" name="comment" placeholder="Комментарий звонка ЛПР">
            <?= HTMLHelper::_('form.token'); ?>
            <button class="se-btn" type="submit">Разговор с ЛПР</button>
          </form>
          <form method="post">
            <input type="hidden" name="task" value="complete_discovery">
            <input class="se-input" type="text" name="discovery_notes" placeholder="Discovery notes">
            <?= HTMLHelper::_('form.token'); ?>
            <button class="se-btn" type="submit">Заполнить discovery</button>
          </form>
          <form method="post">
            <input type="hidden" name="task" value="plan_demo">
            <input class="se-input" type="datetime-local" name="demo_at">
            <?= HTMLHelper::_('form.token'); ?>
            <button class="se-btn" type="submit">Планировать demo</button>
          </form>
          <form method="post">
            <input type="hidden" name="task" value="complete_demo">
            <?= HTMLHelper::_('form.token'); ?>
            <button class="se-btn" type="submit">Demo проведено</button>
          </form>
          <form method="post">
            <input type="hidden" name="task" value="issue_invoice">
            <input class="se-input" type="number" name="amount" step="0.01" placeholder="Сумма счёта">
            <?= HTMLHelper::_('form.token'); ?>
            <button class="se-btn" type="submit">Выставить счёт</button>
          </form>
          <form method="post">
            <input type="hidden" name="task" value="receive_payment">
            <?= HTMLHelper::_('form.token'); ?>
            <button class="se-btn" type="submit">Получена оплата</button>
          </form>
          <form method="post">
            <input type="hidden" name="task" value="issue_certificate">
            <?= HTMLHelper::_('form.token'); ?>
            <button class="se-btn se-btn-danger" type="submit">Выдать удостоверение</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="se-panel se-card" style="margin-top: 12px;">
      <h2>История событий</h2>
      <table class="se-history">
        <thead>
          <tr>
            <th>ID</th>
            <th>Тип</th>
            <th>Payload</th>
            <th>Дата</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($this->history as $event) : ?>
            <tr>
              <td><?= (int) $event['id']; ?></td>
              <td><?= htmlspecialchars((string) $event['event_type'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><pre class="se-pre"><?= htmlspecialchars(json_encode($event['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?></pre></td>
              <td><?= htmlspecialchars((string) $event['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
