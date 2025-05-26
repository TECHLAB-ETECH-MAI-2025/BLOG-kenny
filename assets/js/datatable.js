// Importation des dépendances
import $ from 'jquery';
import 'datatables.net';
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';
import 'datatables.net-buttons-bs5';
import 'datatables.net-buttons/js/buttons.html5';
import 'datatables.net-buttons/js/buttons.print';
import 'datatables.net-buttons/js/buttons.colVis';

// Configuration commune pour tous les datatables
const commonConfig = {
    processing: true,
    serverSide: true,
    responsive: true,
    dom: '<"card"<"card-header py-3"<"row align-items-center"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6">>><"card-body px-0 pb-0"t><"card-footer"<"row align-items-center"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4 text-center"i><"col-sm-12 col-md-4"p>>>>',
    buttons: [
        {
            extend: 'collection',
            text: '<i class="fas fa-download me-1"></i> Exporter',
            className: 'btn-sm btn-primary',
            buttons: [
                {
                    extend: 'copy',
                    text: '<i class="fas fa-copy me-1"></i> Copier',
                    className: 'btn-sm'
                },
                {
                    extend: 'csv',
                    text: '<i class="fas fa-file-csv me-1"></i> CSV',
                    className: 'btn-sm'
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel me-1"></i> Excel',
                    className: 'btn-sm'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf me-1"></i> PDF',
                    className: 'btn-sm'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print me-1"></i> Imprimer',
                    className: 'btn-sm'
                }
            ]
        },
        {
            extend: 'colvis',
            text: '<i class="fas fa-columns me-1"></i> Colonnes',
            className: 'btn-sm btn-secondary'
        },
        {
            text: '<i class="fas fa-sync-alt me-1"></i> Actualiser',
            className: 'btn-sm btn-info',
            action: function (e, dt) {
                dt.ajax.reload();
            }
        }
    ],
    language: {
        processing: "Traitement en cours...",
        search: "Rechercher&nbsp;:",
        lengthMenu: "Afficher _MENU_ éléments",
        info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
        infoEmpty: "Affichage de 0 à 0 sur 0 élément",
        infoFiltered: "(filtré de _MAX_ éléments au total)",
        infoPostFix: "",
        loadingRecords: "Chargement en cours...",
        zeroRecords: "Aucun élément à afficher",
        emptyTable: "Aucune donnée disponible",
        paginate: {
            first: "Premier",
            previous: "Précédent",
            next: "Suivant",
            last: "Dernier"
        }
    },
    pageLength: 10,
    lengthMenu: [[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, "Tous"]],
    stateSave: true,
    stateDuration: 60 * 60 * 24 * 7, // 7 jours
    searchDelay: 350,
    ajax: {
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        error: function (xhr, error, thrown) {
            console.error('Erreur DataTable:', error);
            const errorMessage = xhr.responseJSON?.message || 'Une erreur est survenue lors du chargement des données';
            alert(errorMessage);
        }
    },
    initComplete: function (settings, json) {
        // Ajouter le gestionnaire de recherche personnalisé
        $('#datatable-search').on('keyup', function () {
            const table = $(settings.nTable).DataTable();
            table.search(this.value).draw();
        });
    }
};

$(document).ready(function () {
    // Configuration articles
    if ($('#articles-table').length > 0) {
        $('#articles-table').DataTable({
            ...commonConfig,
            ajax: {
                ...commonConfig.ajax,
                url: '/api/articles',
                data: function (d) {
                    // Ajouter les paramètres de recherche et de tri
                    return $.extend({}, d, {
                        filters: {
                            search: d.search.value,
                            orderBy: d.order[0] ? d.columns[d.order[0].column].data : 'id',
                            orderDir: d.order[0] ? d.order[0].dir : 'desc'
                        }
                    });
                }
            },
            columns: [
                {
                    data: 'id',
                    name: 'ID',
                    className: 'text-center'
                },
                {
                    data: 'title',
                    name: 'Titre',
                    render: function (data, type, row) {
                        if (type === 'display') {
                            return `<div class="d-flex align-items-center">
                                <div class="rounded bg-light d-flex align-items-center justify-content-center me-2" 
                                     style="width: 40px; height: 40px;">
                                    <i class="fas fa-newspaper text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-bold">${data.length > 40 ? data.substr(0, 37) + '...' : data}</div>
                                    <small class="text-muted">${row.excerpt ? (row.excerpt.length > 50 ? row.excerpt.substr(0, 47) + '...' : row.excerpt) : ''}</small>
                                </div>
                            </div>`;
                        }
                        return data;
                    }
                },
                {
                    data: 'categories',
                    name: 'Catégories',
                    render: function (data) {
                        if (Array.isArray(data)) {
                            return data.map(cat =>
                                `<span class="badge bg-secondary me-1">${cat.name}</span>`
                            ).join('');
                        }
                        return data;
                    }
                },
                {
                    data: 'commentsCount',
                    name: 'Commentaires',
                    className: 'text-center',
                    render: function (data) {
                        return `<span class="badge bg-info">${data}</span>`;
                    }
                },
                {
                    data: 'createdAt',
                    name: 'Date',
                    render: function (data) {
                        const date = new Date(data);
                        return `<div class="text-nowrap">
                            <div><i class="far fa-calendar-alt me-1"></i>${date.toLocaleDateString('fr-FR')}</div>
                            <small class="text-muted">
                                <i class="far fa-clock me-1"></i>${date.toLocaleTimeString('fr-FR')}
                            </small>
                        </div>`;
                    }
                },
                {
                    data: 'actions',
                    name: 'Actions',
                    className: 'text-end',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[4, 'desc']],
            drawCallback: function () {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
    }

    // Configuration catégories
    if ($('#categories-table').length > 0) {
        $('#categories-table').DataTable({
            ...commonConfig,
            ajax: {
                ...commonConfig.ajax,
                url: '/api/categories',
                data: function (d) {
                    return $.extend({}, d, {
                        filters: {
                            search: d.search.value,
                            orderBy: d.order[0] ? d.columns[d.order[0].column].data : 'name',
                            orderDir: d.order[0] ? d.order[0].dir : 'asc'
                        }
                    });
                }
            },
            columns: [
                {
                    data: 'id',
                    name: 'ID',
                    className: 'text-center'
                },
                {
                    data: 'name',
                    name: 'Nom',
                    render: function (data, type, row) {
                        if (type === 'display') {
                            return `<div class="fw-bold">${data}</div>`;
                        }
                        return data;
                    }
                },
                {
                    data: 'description',
                    name: 'Description',
                    render: function (data, type, row) {
                        if (type === 'display' && data) {
                            return data.length > 100 ? `<span title="${data}">${data.substr(0, 97)}...</span>` : data;
                        }
                        return data;
                    }
                },
                {
                    data: 'articlesCount',
                    name: 'Articles',
                    className: 'text-center',
                    render: function (data) {
                        return `<span class="badge bg-primary">${data}</span>`;
                    }
                },
                {
                    data: 'updatedAt',
                    name: 'Mise à jour',
                    render: function (data) {
                        const date = new Date(data);
                        return `<div class="text-nowrap">
                            <div><i class="far fa-calendar-alt me-1"></i>${date.toLocaleDateString('fr-FR')}</div>
                        </div>`;
                    }
                },
                {
                    data: 'actions',
                    name: 'Actions',
                    className: 'text-end',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[1, 'asc']]
        });
    }

    // Configuration commentaires
    if ($('#comments-table').length > 0) {
        $('#comments-table').DataTable({
            ...commonConfig,
            ajax: {
                ...commonConfig.ajax,
                url: '/api/comments',
                data: function (d) {
                    return $.extend({}, d, {
                        filters: {
                            search: d.search.value,
                            orderBy: d.order[0] ? d.columns[d.order[0].column].data : 'createdAt',
                            orderDir: d.order[0] ? d.order[0].dir : 'desc'
                        }
                    });
                }
            },
            columns: [
                {
                    data: 'id',
                    name: 'ID',
                    className: 'text-center'
                },
                {
                    data: 'content',
                    name: 'Contenu',
                    render: function (data, type, row) {
                        if (type === 'display') {
                            const truncated = data.length > 100 ? data.substr(0, 97) + '...' : data;
                            return `<div class="comment-content">
                                <p class="mb-0">${truncated}</p>
                                ${data.length > 100 ? `
                                    <button class="btn btn-link btn-sm p-0 mt-1 show-more-btn">
                                        <i class="fas fa-plus-circle me-1"></i>Voir plus
                                    </button>
                                    <div class="full-content d-none">${data}</div>
                                ` : ''}
                            </div>`;
                        }
                        return data;
                    }
                },
                {
                    data: 'article',
                    name: 'Article',
                    render: function (data) {
                        return data.title ? `<a href="/article/${data.id}" class="text-decoration-none">
                            ${data.title.length > 30 ? data.title.substr(0, 27) + '...' : data.title}
                        </a>` : 'N/A';
                    }
                },
                {
                    data: 'author',
                    name: 'Auteur',
                    render: function (data) {
                        if (!data) return 'N/A';
                        return `<div class="d-flex align-items-center">
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" 
                                 style="width: 32px; height: 32px;">
                                ${data.email.charAt(0).toUpperCase()}
                            </div>
                            <div class="text-truncate" style="max-width: 150px;">
                                ${data.email}
                            </div>
                        </div>`;
                    }
                },
                {
                    data: 'createdAt',
                    name: 'Date',
                    render: function (data) {
                        const date = new Date(data);
                        const now = new Date();
                        const diff = now - date;
                        const days = Math.floor(diff / (1000 * 60 * 60 * 24));

                        let timeAgo;
                        if (days === 0) {
                            const hours = Math.floor(diff / (1000 * 60 * 60));
                            if (hours === 0) {
                                const minutes = Math.floor(diff / (1000 * 60));
                                timeAgo = `il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
                            } else {
                                timeAgo = `il y a ${hours} heure${hours > 1 ? 's' : ''}`;
                            }
                        } else if (days === 1) {
                            timeAgo = 'hier';
                        } else if (days < 7) {
                            timeAgo = `il y a ${days} jour${days > 1 ? 's' : ''}`;
                        } else {
                            timeAgo = date.toLocaleDateString('fr-FR');
                        }

                        return `<div class="text-nowrap">
                            <div>${timeAgo}</div>
                            <small class="text-muted">
                                <i class="far fa-clock me-1"></i>${date.toLocaleTimeString('fr-FR')}
                            </small>
                        </div>`;
                    }
                },
                {
                    data: 'actions',
                    name: 'Actions',
                    className: 'text-end',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[4, 'desc']],
            rowCallback: function (row, data) {
                if (data.content.toLowerCase().includes('urgent')) {
                    $(row).addClass('table-warning');
                }
            },
            initComplete: function (settings, json) {
                // Gestionnaire pour le bouton "Voir plus"
                $(document).on('click', '.show-more-btn', function (e) {
                    e.preventDefault();
                    const content = $(this).siblings('.full-content').html();
                    const modal = $(`
                        <div class="modal fade" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Contenu complet</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">${content}</div>
                                </div>
                            </div>
                        </div>
                    `);
                    modal.modal('show');
                    modal.on('hidden.bs.modal', function () {
                        modal.remove();
                    });
                });
            }
        });
    }
});
