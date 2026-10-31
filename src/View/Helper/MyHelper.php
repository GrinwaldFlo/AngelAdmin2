<?php
/* src/View/Helper/PdfHelper.php */
namespace App\View\Helper;
use App\Controller\PrefResult;
use Cake\View\Helper;

class MyHelper extends Helper
{
    public array $helpers = ['Html', 'Form'];
    public $rowColor = 0;

    public function PresenceStateToHtml($state)
    {
        switch ($state) {
            case 0: // Absent
                return 'danger';
            case 1: // Present
                return 'success';
            case 2: // Excused
                return 'primary';
            case 3: // Late
                return 'warning';
            case -1: // No
                return 'black';
            default:
                return "XXX";
        }
    }

    public function LanguageLinks($config)
    {
        $lng = $this->Languages($config);

        $r = "";
        foreach ($lng as $l) {
            $r = $r . $this->getView()->Html->link($l[0], ['controller' => 'Users', 'action' => 'setLanguage', $l[1]]) . ' ';
        }
        return $r;
    }

    public function Languages($config)
    {
        $r = [];
        $languages = [
            'fr' => __('French'),
            'de' => __('German'),
            'en' => __('English'),
            'es' => __('Spanish'),
            'it' => __('Italian'),
        ];

        foreach ($languages as $code => $label) {
            if (!empty($config[$code] ?? null)) {
                $r[] = [$label, $code];
            }
        }

        return $r;
    }

    public function ImageFromBlob($res)
    {
        $content = stream_get_contents($res);
        if (empty($content))
            return "";
        return '<img src="data:image/png;base64,' . explode(",", $content)[1] . '" />';
    }

    public function DataFromBlob($res)
    {
        $content = stream_get_contents($res);
        if (empty($content))
            return "";
        return base64_decode(explode(",", $content)[1]);
    }

    public function InfoTable($header, $data)
    {
        echo '<table class="table table-striped table-hover table-sm">';
        if (!empty($header)) {
            echo "<thead><tr>";
            foreach ($header as $value) {
                echo "<th>$value</th>";
            }
            echo "</tr></thead>";
        }
        echo "<tbody>";

        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $col) {
                echo "<td>$col</td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table>";
    }

    public function adminButtons($pageId): string
    {
        $r = $this->getView()->Html->link(__('Main configuration'), ['controller' => 'configurations', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm' . ($pageId == 1 ? ' pressed' : '')]);
        $r .= $this->getView()->Html->link(__('Locations'), ['controller' => 'sites', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm' . ($pageId == 2 ? ' pressed' : '')]);
        $r .= $this->getView()->Html->link(__('Custom fields'), ['controller' => 'field_types', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm' . ($pageId == 3 ? ' pressed' : '')]);

        return $r;
    }

    public function InputFields($member, $edit)
    {
        foreach ($member->fields as $field) {
            if (($edit || $field->field_type->member_edit) && !$field->field_type->hidden) {
                switch ($field->field_type->style) {
                    case 0:
                    case 1:
                    case 2:
                        echo $this->getView()->Form->control('field.' . $field->field_type_id, ['value' => $field->value, 'label' => $field->field_type->label, 'required' => $field->field_type->mandatory && !$edit]);
                        break;
                    case 3:
                        echo $this->getView()->Form->control('field.' . $field->field_type_id, ['value' => $field->value, 'type' => 'number', 'label' => $field->field_type->label, 'required' => $field->field_type->mandatory && !$edit]);
                        break;
                    case 4:
                        echo $this->getView()->Form->control('field.' . $field->field_type_id, ['value' => empty($field->value), 'type' => 'checkbox', 'label' => $field->field_type->label, 'required' => $field->field_type->mandatory && !$edit]);
                        break;
                    case 5:
                        echo $this->getView()->Form->control('field.' . $field->field_type_id, ['value' => $field->value, 'type' => 'date', 'label' => $field->field_type->label, 'required' => $field->field_type->mandatory && !$edit]);
                        break;
                    case 6:
                        echo "";
                        break;
                    case 7:
                        echo $this->getView()->Form->control('field.' . $field->field_type_id, [
                            'type' => 'select', // specify that this is a dropdown
                            'options' => [
                                '' => '',
                                'YS' => 'YS',
                                'YM' => 'YM',
                                'YL' => 'YL',
                                'XS' => 'XS',
                                'S' => 'S',
                                'M' => 'M',
                                'L' => 'L',
                                'XL' => 'XL',
                                'XXL' => 'XXL'
                            ], // the new options for the dropdown
                            'value' => $field->value, // the currently selected value
                            'label' => $field->field_type->label,
                            'required' => $field->field_type->mandatory && !$edit
                        ]);
                        break;
                }
            }
        }
    }

    public function fieldArray($fields, $forceShow = false)
    {
        $r = "";
        foreach ($fields as $field) {
            if (!$field->field_type->hidden) {
                $this->respGrid($field->field_type->label, $field->value, $field->field_type->mandatory, $forceShow);
            }
        }
        return $r;
    }

    public function multiPaymentList()
    {
        return [1 => 1, 2 => 2, 4 => 4];
    }

    public function echo($element, $value, $option = null)
    {
        echo $this->tags($element, $value, $option);
    }

    public function tags($element, $value, $option = null): string
    {
        if (empty($element))
            return $value;

        $opt = "";
        if (!empty($option)) {
            foreach ($option as $key => $val) {
                $opt .= sprintf('%1$s="%2$s" ', $key, $val);
            }
        }

        return sprintf('<%1$s %3$s>%2$s</%1$s>', $element, $value, $opt);
    }

    public function showEvent($title, $comment, $meetings, $curRole, $config)
    {
        echo '<div class="col-sm-12 col-md-4">';
        $this->echo("h4", strip_tags($title), null);
        if ($comment)
            $this->echo("i", strip_tags($comment), null);
        echo '<ul>';
        foreach ($meetings as $meet) {
            $label = "";
            if ($curRole->MemberViewAll) {
                $label = $this->getView()->Html->link(__('{0} - {1}', $meet->meeting_date->i18nFormat($config['dateEvent']), $meet->name), ['controller' => 'Meetings', 'action' => 'view', $meet->id]);
            } else {
                $label = h(__('{0} - {1}', $meet->meeting_date->i18nFormat($config['dateEvent']), $meet->name));
            }

            if ($meet->webUrl())
                $label .= ' ' . $this->getView()->Html->link(__('Link'), $meet->webUrl(), ['target' => '_blank', 'rel' => 'noopener']);

            if ($meet->doodle && $meet->my == null) {
                $label .= ' ' . $this->getView()->Form->postLink(__('Join'), ['controller' => 'meetings', 'action' => 'join', $meet->id], ['confirm' => __('Are you sure you want to join the event ?'), 'class' => 'btn btn-primary btn-sm']);
            }
            if ($meet->doodle && $meet->my != null && $meet->my->state == 1) {
                $label .= ' ' . $this->tags('span', __('Registered'), ['class' => "badge bg-info"]);
            }

            $this->echo("li", $label, ['class' => "bullet star"]);
        }
        echo '</ul>';
        echo '</div>';
    }

    public function respGrid($label, $value, $mandatory = false, $forceShow = false)
    {
        if ($this->rowColor) {
            $backColor = ' divTableColor';
            $this->rowColor = 0;
        } else {
            $backColor = '';
            $this->rowColor = 1;
        }

        if ($value || ($forceShow && !$mandatory)) {
            echo $this->tags(
                'div',
                $this->tags('div', $label, ['class' => 'col-12 col-md-5 divTableLabel' . $backColor]) .
                $this->tags('div', $value, ['class' => 'col-12 col-md-7' . $backColor])
                ,
                ['class' => 'row']
            );
        } else {
            if ($mandatory) {
                echo $this->tags(
                    'div',
                    $this->tags('div', $label, ['class' => 'col-12 col-md-5 divTableLabel' . $backColor]) .
                    $this->tags('div', __("MISSING"), ['class' => 'col-12 col-md-7' . $backColor . ' missing'])
                    ,
                    ['class' => 'row']
                );
            }
        }
    }

    /**
     * Generates a styled photo link button.
     *
     * @param bool $edit The icon is to edit (true) or add (false).
     * @param string $action The controller action to link to (e.g., 'addPhoto').
     * @param int|string $memberId The member ID to pass as a parameter.
     * @return string The generated HTML link.
     */
    public function butPhotoAddEdit($edit, $action, $memberId)
    {
        $iconPhoto = $edit ? '<i class="gg-pen"></i>' : '<i class="gg-add-r"></i>';

        return $this->getView()->Html->link(
            $iconPhoto,
            ['action' => $action, $memberId],
            [
                'class' => 'ml-auto mr-3 photo-btn ' . ($edit ? 'photo-btn-edit' : 'photo-btn-add'),
                'escape' => false,
            ]
        );
    }

    public function icon($name, $options = []): string
    {
        $class = 'material-icons';
        if (!empty($options['class'])) {
            $class .= ' ' . $options['class'];
        }
        return '<span class="' . h($class) . '">' . h($name) . '</span>';
    }

    public function symbol($name, $options = []): string
    {
        $class = 'material-symbols';
        if (!empty($options['class'])) {
            $class .= ' ' . $options['class'];
        }

        return '<span class="' . h($class) . '">' . h($name) . '</span>';
    }

    /**
     * Member status filter buttons, a pressed filter links back to 0 (no filter)
     * Coaches only see the active members of their teams: Active, Alumni and No team are hidden to them
     * @param array $url Url without the filter, which is appended as last param
     * @param int|null $memberFilter Current filter
     * @param int[] $filters Filters to show (1 Active, 2 Alumni, 3 Membership not paid, 4 Non inscrit, 5 No team)
     */
    public function memberFilterLinks(array $url, $memberFilter, array $filters = [1, 2, 3]): string
    {
        $labels = [1 => __('Active'), 2 => __('Alumni'), 3 => __('Membership not paid'), 4 => __('Non inscrit'), 5 => __('No team')];
        $scopedHidden = [1, 2, 5];
        $isScoped = (bool)$this->getView()->get('isScoped', false);

        $links = '';
        foreach ($filters as $filter) {
            if ($isScoped && in_array($filter, $scopedHidden, true)) {
                continue;
            }
            $isCurrent = $memberFilter == $filter;
            $links .= $this->getView()->Html->link(
                $labels[$filter],
                array_merge($url, [$isCurrent ? 0 : $filter]),
                ['class' => 'btn btn-primary btn-sm' . ($isCurrent ? ' pressed' : '')]
            ) . "\n";
        }
        return $links;
    }

    public function teamLinks(PrefResult $pref, string $action): string
    {
        $links = '';
        foreach ($pref->teams as $key => $team) {
            $isCurrent = $key == $pref->teamId;
            $url = [
                'action' => $action,
                $isCurrent ? 0 : $key,
                $pref->memberFilter
            ];
            $class = 'btn btn-primary btn-sm' . ($isCurrent ? ' pressed' : '');
            $links .= $this->getView()->Html->link(
                $team,
                $url,
                ['class' => $class]
            );
        }
        return $links;
    }
}
