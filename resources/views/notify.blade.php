<?php
// Blade partial: push flash messages onto a global queue so the frontend bundle
// can drain and display them once toastr is available. This avoids timing
// issues when the Blade script runs before the Vite bundle.
?>

<script>
    (function() {
        window.__laravel_toastr_queue = window.__laravel_toastr_queue || [];

        <?php
        $types = ['success', 'error', 'info', 'warning'];
        foreach ($types as $t) {
            $val = session($t);
            if (!$val) continue;
            if (is_array($val)) {
                $val = implode("\n", $val);
            }
            // json_encode to safely escape string for JS
            $js = json_encode((string) $val);
            echo "window.__laravel_toastr_queue.push({ type: '{$t}', message: {$js} });\n";
        }
        ?>
    })();
</script>
