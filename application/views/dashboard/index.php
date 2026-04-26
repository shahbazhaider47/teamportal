<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="row g-3">

        <?php
        // Define your layout structure. Key is data-container/id, value is bootstrap col class
        $layout = [
            ['id' => 'top-col-1', 'col' => 'col-md-3'],
            ['id' => 'top-col-2', 'col' => 'col-md-3'],
            ['id' => 'top-col-3', 'col' => 'col-md-3'],
            ['id' => 'top-col-4', 'col' => 'col-md-3'],
            ['id' => 'row3-col-1', 'col' => 'col-md-8'],
            ['id' => 'row3-col-2', 'col' => 'col-md-4'],
            //['id' => 'row4-col-1', 'col' => 'col-md-6'],
            //['id' => 'row4-col-2', 'col' => 'col-md-6'],
            //['id' => 'bottom-col-1', 'col' => 'col-md-3'],
            //['id' => 'bottom-col-2', 'col' => 'col-md-3'],
            //['id' => 'bottom-col-3', 'col' => 'col-md-6'],
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
                // Show ID and register code in the placeholder
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