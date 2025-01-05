<?php
namespace Txtlog\Includes;

use Txtlog\Core\Common;

// Dashboard helper functions
class Dashboard {
  /**
   * Get the main log dashboard HTML
   *
   * @param output JSON data to format as HTML
   * @return string
   */
  public static function getHTML($output) {
    $name = Common::getString($output->log->name ?? str_replace('_', ' ', $output->error ?? '')) ?: 'Txtlog dashboard';
    $create = isset($output->log->create) ? "Created: {$output->log->create}<br>" : '';
    $authorization = $output->log->authorization ?? '' == 'yes' ? 'Password protected: '.$output->log->authorization.'<br>' : '';
    $dateError = isset($output->log->date_error) ? ' is-danger' : '';
    $rowText = isset($output->error) ? '' : 'Logs: '.Common::formatReadable($output->log->total_rows ?? 0).'<br>';
    $warning = $output->log->warning ?? '';
    $resetUrl = $output->log->base_url ?? '';

    $date = Common::get('date', 0, true);
    $data = Common::get('data', 0, true);

    $nav = '';
    if(!empty($output->rows)) {
      $nav = <<<END
              <div class="columns is-mobile">
                <div class="column is-one-third">
                  <a class="button is-small" title="Newer data" href="{$output->log->prev}">&nbsp;&lt;&nbsp;</a>
                </div>
                <div class="column is-third">
                </div>
                <div class="column is-one-third is-right">
                  <a class="button is-small" title="Older data" href="{$output->log->next}">&nbsp;&gt;&nbsp;</a>
                </div>
              </div>
      END;
    }

    $html = <<<END
        <section class="hero">
          <div class="section hero-body">
            <p class="title">
              $name
            </p>
            <p>
              $rowText
              $create
              $authorization
            </p>
          </div>
        </section>
        <form method="GET">
         <div class="section dashboard pt-0">
    END;

    if(!isset($output->error)) {
      $html .= <<<END
            <div class="columns is-mobile mb-0">
              <div class="column is-one-quarter">
                <label class="label" for="date">Date</label>
              </div>
              <div class="column is-one-half">
                <label class="label" for="data">Search</label>
              </div>
              <div class="column is-one-quarter is-right">
                <a class="button is-small" href="$resetUrl">&nbsp;Reset&nbsp;&nbsp;</a>
              </div>
            </div>
            <div class="columns is-mobile mb-5">
              <div class="column is-one-quarter mobwide">
                <input class="input is-small{$dateError}" type="text" name="date" id="date" placeholder="yyyy-mm-dd hh:mm:ss" value="$date" id="date">
              </div>
              <div class="column is-one-half mobwide">
                <input class="input is-small" type="text" name="data" id="data" placeholder="Search" value="$data">
              </div>
              <div class="column is-one-quarter is-right">
                <input class="button is-small" type="submit" value="Search">
              </div>
            </div>
            $nav
      END;
    }

    $i = 0;
    foreach($output->rows ?? [] as $row) {
      // Add stripes to even/uneven rows
      $i++;
      $class = $i % 2 == 0 ? ' striped' : '';

      $date = $row->date;
      $date = Common::getString($date);
      $date = str_replace(' ', '<br>', $date);

      $logdata = $row->log;
      // Non-json log
      if(isset($logdata->data) && !Common::isJson($logdata->data)) {
        $logdata = $logdata->data;
      } else {
        $logdata = json_encode($logdata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      }
      $logdata = Common::getString($logdata);

      $html .= <<<END
            <div class="columns is-gapless is-family-monospace pl-1 mb-2$class">
              <div class="column is-2">
                $date
              </div>
              <div class="column logline">$logdata</div>
            </div>

      END;
    }

    $html .= <<<END
        <br>$nav
        <p class="has-text-danger">
          $warning
        </p>
        </div>
        </form>

    END;

    return $html;
  }
}
