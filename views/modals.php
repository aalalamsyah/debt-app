<datalist id="rec"><?php foreach ($recorders as $r): ?><option value="<?= e($r) ?>"><?php endforeach; ?></datalist>

<dialog id="mDebt">
  <form method="post">
    <h3>Catat hutang</h3>
    <?php hid('add_debt', $back);
    memberSelect($members, false); ?>
    <label>Tanggal hutang</label><input type="date" name="debt_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
    <label>Nominal hutang</label><input name="amount" inputmode="numeric" placeholder="0" required oninput="fmt(this)">
    <label>Keterangan <em>(opsional)</em></label><input name="note" maxlength="200" placeholder="Contoh: belanja bulanan">
    <label>Dicatat oleh</label><input name="recorded_by" list="rec" maxlength="100" required placeholder="Nama pencatat">
    <p class="mute" style="font-size:13px;margin:12px 0 0">Isi tanggal sebelumnya jika lupa mencatat. Tanggal tidak bisa melebihi hari ini.</p>
    <div class="foot"><button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button><button class="btn pri">Simpan hutang</button></div>
  </form>
</dialog>

<dialog id="mPay">
  <form method="post">
    <h3>Pelunasan hutang</h3>
    <?php hid('add_payment', $back);
    memberSelect($members, true); ?>
    <label>Nominal pelunasan</label>
    <div class="row"><input name="amount" id="payAmt" inputmode="numeric" placeholder="0" required oninput="fmt(this)"><button type="button" class="btn" onclick="payAll()">Lunas penuh</button></div>
    <label>Keterangan <em>(opsional)</em></label><input name="note" maxlength="200" placeholder="Contoh: bayar tunai">
    <label>Dicatat oleh</label><input name="recorded_by" list="rec" maxlength="100" required placeholder="Nama pencatat">
    <div class="foot"><button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button><button class="btn pri">Simpan pelunasan</button></div>
  </form>
</dialog>

<dialog id="mMember">
  <form method="post">
    <h3>Tambah member</h3>
    <?php hid('add_member', $back); ?>
    <label>Nama member</label><input name="name" maxlength="100" required placeholder="Nama penghutang">
    <label>No. telepon <em>(opsional)</em></label><input name="phone" maxlength="30" inputmode="tel">
    <div class="foot"><button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button><button class="btn pri">Simpan member</button></div>
  </form>
</dialog>
<dialog id="mEditMember">
  <form method="post">
    <h3>Edit data member</h3>
    <?php hid('update_member', '?page=members'); ?>
    <input type="hidden" name="member_id" id="editMemberId">
    <label>Nama member</label><input name="name" id="editMemberName" maxlength="100" required>
    <label>No. telepon <em>(opsional)</em></label><input name="phone" id="editMemberPhone" maxlength="30" inputmode="tel">
    <div class="foot"><button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button><button class="btn pri">Simpan perubahan</button></div>
  </form>
</dialog>


<dialog id="appActionDialog" class="app-action-dialog" aria-labelledby="appActionTitle">
  <form method="dialog" class="app-action-form">
    <span class="app-dialog-icon" id="appActionIcon" aria-hidden="true">!</span>
    <h3 id="appActionTitle">Konfirmasi tindakan</h3>
    <p id="appActionMessage" class="app-dialog-message"></p>
    <label id="appActionReasonLabel" for="appActionReason" hidden>Alasan pembatalan</label>
    <textarea id="appActionReason" rows="3" maxlength="500" placeholder="Tuliskan alasan pembatalan..." hidden></textarea>
    <div class="foot">
      <button type="button" class="btn" id="appActionCancel">Kembali</button>
      <button type="button" class="btn pri" id="appActionConfirm">Lanjutkan</button>
    </div>
  </form>
</dialog>
