<?php
// Settings panel Otomoto — Auranet 2026-05-29
$msg = $this->session->flashdata('msg');
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3>Otomoto — Ustawienia i status</h3>
    </div>
    <div class="panel-body">

        <?php if (!empty($msg)): ?>
            <div class="alert alert-<?= htmlspecialchars($msg[0]); ?>"><?= htmlspecialchars($msg[1]); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <h4>Status</h4>
                <table class="table table-striped">
                    <tr><td>Tryb pracy</td><td><strong><?= htmlspecialchars($mode ?: '—'); ?></strong></td></tr>
                    <tr><td>Typ konta</td><td><?= htmlspecialchars($client_type ?: '—'); ?></td></tr>
                    <tr><td>Login</td><td><?= htmlspecialchars($username ?: '—'); ?></td></tr>
                    <tr><td>Hasło zapisane</td><td><?= $has_password ? '<span style="color:green;">✓ tak</span>' : '<span style="color:red;">✗ brak</span>'; ?></td></tr>
                    <tr><td>Token API</td><td>
                        <?php if ($token_valid): ?>
                            <span style="color:green;">✓ ważny</span> (jeszcze <?= $token_remaining_h; ?>h, do <?= $token_exp ? date('Y-m-d H:i', $token_exp) : '—'; ?>)
                        <?php else: ?>
                            <span style="color:orange;">⚠ wygasł / pusty</span> — pobierze się przy następnym wystawieniu
                        <?php endif; ?>
                    </td></tr>
                </table>

                <h4>Kolejka produktów</h4>
                <table class="table table-striped">
                    <tr><td>Wystawione na Otomoto</td><td><strong><?= $total_listed; ?></strong></td></tr>
                    <tr><td>Czekają z ceną (wystawialne)</td><td><strong style="color:<?= $waiting_with_price > 0 ? 'orange' : 'green'; ?>;"><?= $waiting_with_price; ?></strong></td></tr>
                    <tr><td>Czekają BEZ ceny (uzupełnij cenę w panelu produktu)</td><td><strong style="color:<?= $waiting_no_price > 0 ? 'red' : 'green'; ?>;"><?= $waiting_no_price; ?></strong></td></tr>
                </table>
            </div>

            <div class="col-md-6">
                <h4>Akcje</h4>
                <p>
                    <button id="btn-test" class="btn btn-info">Sprawdź połączenie z Otomoto</button>
                    <span id="test-result" style="margin-left:10px;"></span>
                </p>
                <form method="POST" action="<?= site_url('duocms/Otomoto/refresh_token_now'); ?>" style="display:inline;">
                    <button type="submit" class="btn btn-warning">Wymuś odświeżenie tokenu</button>
                </form>

                <hr>

                <h4>Zmiana credentials</h4>
                <form method="POST" action="<?= site_url('duocms/Otomoto/save_credentials'); ?>">
                    <div class="form-group">
                        <label>Login (email konta Otomoto)</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username); ?>" />
                    </div>
                    <div class="form-group">
                        <label>Hasło (puste = nie zmieniaj)</label>
                        <div class="input-group">
                            <input type="password" id="pwd" name="password" class="form-control" placeholder="Wpisz nowe hasło" autocomplete="new-password" />
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" id="btn-show-pwd">Pokaż</button>
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Tryb</label>
                        <select name="mode" class="form-control">
                            <option value="production" <?= $mode == 'production' ? 'selected' : ''; ?>>production (otomoto.pl)</option>
                            <option value="sandbox" <?= $mode == 'sandbox' ? 'selected' : ''; ?>>sandbox</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(function(){
    $('#btn-show-pwd').on('click', function(){
        var f = $('#pwd');
        if (f.attr('type') === 'password') { f.attr('type', 'text'); $(this).text('Ukryj'); }
        else { f.attr('type', 'password'); $(this).text('Pokaż'); }
    });

    $('#btn-test').on('click', function(){
        var $btn = $(this), $res = $('#test-result');
        $btn.prop('disabled', true);
        $res.html('<em>Testuję...</em>');
        $.getJSON('<?= site_url('duocms/Otomoto/test_connection'); ?>', function(data){
            if (data.ok) {
                $res.html('<span style="color:green;">✓ OK — token pobrany (' + data.token_preview + '), ważny ' + Math.round(data.expires_in/3600) + 'h</span>');
            } else {
                $res.html('<span style="color:red;">✗ FAIL — ' + (data.error || 'nieznany błąd') + ' (HTTP ' + data.http + ')</span>');
            }
            $btn.prop('disabled', false);
        }).fail(function(){
            $res.html('<span style="color:red;">✗ Błąd sieciowy</span>');
            $btn.prop('disabled', false);
        });
    });
});
</script>
