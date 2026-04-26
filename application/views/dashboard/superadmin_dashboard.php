<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="row g-3">
        
        <?php
        $layout = [
            ['id' => 'admin-top-col-0', 'col' => 'col-md-12'],
            ['id' => 'admin-top-col-1', 'col' => 'col-md-3'],
            ['id' => 'admin-top-col-2', 'col' => 'col-md-3'],
            ['id' => 'admin-top-col-3', 'col' => 'col-md-6'],
            ['id' => 'admin-row3-col-1', 'col' => 'col-md-8'],
            ['id' => 'admin-row3-col-2', 'col' => 'col-md-4'],
        ];

        foreach ($layout as $box) {
            $id = $box['id'];
            echo '<div class="'.$box['col'].' " data-container="'.$id.'">';
            ob_start();
            render_dashboard_widgets($id);
            $widget_html = trim(ob_get_clean());
            if ($widget_html) {
                echo $widget_html;
            } else {

                echo '
                    <div class="card text-center text-muted p-5 my-2 border-dashed">
                        <div>
                            <span class="d-block mb-1">Add your widget here</span>
                            <span class="d-block small mb-1">ID: '.$id.'</span>
                        </div>
                    </div>
                ';
            }
            echo '</div>';
        }
        ?>

    </div>
</div>