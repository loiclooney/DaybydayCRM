@extends('layouts.master')

@section('content')
    <div class="container mt-5">
        @if(session('success'))
            <div class="alert alert-success text-center">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger text-center">{{ session('error') }}</div>
        @endif

        <h1 class="mb-4 text-center">⚙️ Paramètres de la Base de Données</h1>
        <br>

        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4 bg-light p-4" style="padding-left: 20px; padding-right: 20px;">
                    <div class="card-body">
                        <br>
                        <h3 class="card-title text-center mb-4">📥 Importer des Données</h3>
                        <form action="{{ route('settings.database.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <label for="table_name" class="mt-3">Sélectionner une table :</label>
                            <select id="table_name" name="table_name" class="form-control mb-3" required>
                                <option value="" disabled selected>Veuillez sélectionner une table</option>
                            </select>

                            <label for="csv_file" class="mt-3">Fichier CSV :</label>
                            <input type="file" name="csv_file" class="form-control mb-4" required>
                            <br>
                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-primary px-4 py-2">📥 Importer</button>
                            </div>
                            <br>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow mb-4 bg-light p-4" style="padding-left: 20px; padding-right: 20px;">
                    <div class="card-body">
                        <br>
                        <h3 class="card-title text-center mb-4">📤 Exporter des Données</h3>
                        <label for="export_table_name" class="mt-3">Sélectionner une table :</label>
                        <select id="export_table_name" name="table_name" class="form-control mb-4" required>
                            <option value="" disabled selected>Veuillez sélectionner une table</option>
                        </select>
                        <br>

                        <div class="text-center mt-4 d-flex justify-content-center gap-3">
                            <form id="export_csv_form" action="{{ route('settings.database.export_csv') }}" method="GET">
                                <input type="hidden" name="table_name" id="export_csv_table">
                                <button type="submit" class="btn btn-success px-4 py-2">📄 CSV</button>
                            </form>
                            <br>
                            <form id="export_excel_form" action="{{ route('settings.database.export_excel') }}" method="GET">
                                <input type="hidden" name="table_name" id="export_excel_table">
                                <button type="submit" class="btn btn-info px-4 py-2">📊 Excel</button>
                            </form>
                            <br>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow p-4 mt-5 bg-light" style="padding-left: 20px; padding-right: 20px;margin-top:20px">
            <div class="card-body text-center">
                <br>
                <h3 class="card-title text-danger">⚠️ Réinitialisation de la Base</h3>
                <p class="text-muted">Cette action supprimera toutes les données sauf celles de la table <strong>users</strong>.</p>

                <form action="{{ route('settings.database.reset') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-lg px-5 py-2">
                       🔄 Réinitialiser la Base de Données
                    </button>
                </form>
                <br>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            fetch("{{ route('settings.database.tables') }}")
                .then(response => response.json())
                .then(data => {
                    let tableSelectImport = document.getElementById("table_name");
                    let tableSelectExport = document.getElementById("export_table_name");

                    tableSelectImport.innerHTML = '<option value="" disabled selected>Veuillez sélectionner une table</option>';
                    tableSelectExport.innerHTML = '<option value="" disabled selected>Veuillez sélectionner une table</option>';

                    if (data.length === 0) {
                        tableSelectImport.innerHTML += '<option value="">Aucune table disponible</option>';
                        tableSelectExport.innerHTML += '<option value="">Aucune table disponible</option>';
                    } else {
                        data.forEach(table => {
                            let optionImport = new Option(table, table);
                            let optionExport = new Option(table, table);
                            tableSelectImport.appendChild(optionImport);
                            tableSelectExport.appendChild(optionExport);
                        });
                    }
                })
                .catch(error => console.error("Erreur lors du chargement des tables :", error));

            // Ajout d'un événement pour récupérer la valeur de la table sélectionnée avant d'exporter
            document.getElementById("export_csv_form").addEventListener("submit", function (event) {
                let selectedTable = document.getElementById("export_table_name").value;
                if (!selectedTable) {
                    alert("Veuillez sélectionner une table avant d’exporter.");
                    event.preventDefault();
                } else {
                    document.getElementById("export_csv_table").value = selectedTable;
                }
            });

            document.getElementById("export_excel_form").addEventListener("submit", function (event) {
                let selectedTable = document.getElementById("export_table_name").value;
                if (!selectedTable) {
                    alert("Veuillez sélectionner une table avant d’exporter.");
                    event.preventDefault();
                } else {
                    document.getElementById("export_excel_table").value = selectedTable;
                }
            });
        });
    </script>
@endsection
