<?= $this->extend('templates/admin'); ?>
<?= $this->section('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="btn-toolbar mb-2">
                    <div class="btn-group btn-group-sm my-1 mr-2" role="group">
                        <button type="button" class="btn btn-primary" title="Reload Tabel"><i class="fas fa-sync-alt fa-fw"></i></button>
                    </div>
                </div>
                <table class="table table-bordered table-hover w-100">
                    <thead>
                        <tr>
                            <th class="table-primary align-middle text-center">Nama</th>
                            <th class="table-primary align-middle text-center">NIS</th>
                            <th class="table-primary align-middle text-center">NISN</th>
                            <th class="table-primary align-middle text-center">Jenis<br>Kelamin</th>
                            <th class="table-primary align-middle text-center">Tempat<br>Lahir</th>
                            <th class="table-primary align-middle text-center">Tanggal<br>Lahir</th>
                            <th class="table-primary align-middle text-center">Alamat</th>
                            <th class="table-primary align-middle text-center">Nama<br>Orangtua</th>
                            <th class="table-primary align-middle text-center">Rombel</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="overlay">
            <i class="fas fa-2x fa-sync-alt fa-spin"></i>
        </div>
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>