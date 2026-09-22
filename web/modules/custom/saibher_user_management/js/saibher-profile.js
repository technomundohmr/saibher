/**
 * @file
 * Handles the "Copy link" button and the AJAX profile photo upload.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * Copies the affiliate link to the clipboard.
   */
  Drupal.behaviors.saibherProfileCopyLink = {
    attach: function (context) {
      const buttons = once(
        'saibher-copy-link',
        '.saibher-profile__copy-button',
        context
      );

      buttons.forEach(function (button) {
        button.addEventListener('click', function () {
          const link = button.getAttribute('data-copy-target');

          navigator.clipboard.writeText(link).then(function () {
            const originalText = button.textContent;
            button.textContent = Drupal.t('¡Copiado!');

            setTimeout(function () {
              button.textContent = originalText;
            }, 2000);
          });
        });
      });
    },
  };

  /**
   * Uploads a profile photo without leaving the page.
   *
   * Clicking the "Cambiar foto" button opens the native file picker; the
   * selected image is POSTed to /mi-perfil/foto and, on success, the avatar
   * is swapped in place.
   */
  Drupal.behaviors.saibherProfilePhoto = {
    attach: function (context) {
      const avatars = once('saibher-photo', '[data-saibher-avatar]', context);

      avatars.forEach(function (avatar) {
        const trigger = avatar.querySelector('[data-saibher-photo-trigger]');
        const input = avatar.querySelector('[data-saibher-photo-input]');

        if (!trigger || !input) {
          return;
        }

        trigger.addEventListener('click', function () {
          input.click();
        });

        input.addEventListener('change', function () {
          if (!input.files || !input.files.length) {
            return;
          }

          const formData = new FormData();
          formData.append('file', input.files[0]);

          const originalText = trigger.textContent;
          trigger.disabled = true;
          trigger.textContent = Drupal.t('Subiendo…');

          fetch(Drupal.url('mi-perfil/foto'), {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
            },
          })
            .then(function (response) {
              return response.json().catch(function () {
                throw new Error(Drupal.t('Respuesta inválida del servidor.'));
              });
            })
            .then(function (payload) {
              if (payload.status !== 'ok' || !payload.url) {
                throw new Error(
                  payload.message || Drupal.t('No se pudo actualizar la foto.')
                );
              }

              if (window.drupalSettings && drupalSettings.path &&
                drupalSettings.path.currentPath === 'mi-perfil') {
                let imgBox = avatar.querySelector('.saibher-profile__avatar-img');
                if (!imgBox) {
                  imgBox = document.createElement('div');
                  imgBox.className = 'saibher-profile__avatar-img';
                  const icon = avatar.querySelector('.saibher-profile__avatar-icon');
                  if (icon) {
                    icon.remove();
                  }
                  avatar.insertBefore(imgBox, trigger.parentNode ? trigger : avatar.firstChild);
                }
                const img = document.createElement('img');
                img.src = payload.url;
                img.alt = Drupal.t('Foto de perfil');
                imgBox.replaceChildren(img);
              }
            })
            .catch(function (error) {
              window.alert(error.message);
            })
            .finally(function () {
              trigger.disabled = false;
              trigger.textContent = originalText;
              input.value = '';
            });
        });
      });
    },
  };

  /**
   * Affiliate balances table.
   *
   * Consumes the affiliate ledger JSON endpoint and renders the totals strip,
   * the per-person table (sortable), the month filter and the pager. All
   * rendering is functional: no styling assumptions beyond semantic markup.
   */
  Drupal.behaviors.saibherProfileBalances = {
    attach: function attachBalances(context) {
      const roots = once(
        'saibher-balances',
        '[data-saibher-balances]',
        context
      );

      roots.forEach(function build(root) {
        const endpoint = root.getAttribute('data-saibher-endpoint');
        if (!endpoint) {
          return;
        }

        const monthSelect = root.querySelector('[data-saibher-month]');
        const totalsBox = root.querySelector('[data-saibher-totals]');
        const stateBox = root.querySelector('[data-saibher-state]');

        const state = {
          month: monthSelect ? monthSelect.value : '',
          page: 1,
          sort: 'joined',
          dir: 'desc',
        };

        function setState(message) {
          if (stateBox) {
            stateBox.textContent = message;
          }
        }

        function formatMoney(value, currency) {
          const number = Number(value) || 0;
          try {
            return new Intl.NumberFormat('es', {
              style: 'currency',
              currency: currency || 'COP',
              minimumFractionDigits: 0,
              maximumFractionDigits: 2,
            }).format(number);
          }
          catch (e) {
            return (currency || '') + ' ' + number.toLocaleString('es');
          }
        }

        function formatDate(timestamp) {
          if (!timestamp) {
            return '—';
          }
          return new Intl.DateTimeFormat('es', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
          }).format(new Date(Number(timestamp) * 1000));
        }

        function monthLabel(month) {
          if (!month || !month.match(/^\d{4}-\d{2}$/)) {
            return month;
          }
          const [year, m] = month.split('-');
          const date = new Date(Number(year), Number(m) - 1, 1);
          if (Number.isNaN(date.getTime())) {
            return month;
          }
          return new Intl.DateTimeFormat('es', {
            month: 'long',
            year: 'numeric',
          }).format(date);
        }

        function renderTotals(totals) {
          if (!totalsBox) {
            return;
          }
          totalsBox.replaceChildren();

          const blocks = [
            {
              label: Drupal.t('Referidos'),
              value: String(totals.referred_count || 0),
            },
            {
              label: Drupal.t('Compradores'),
              value: String(totals.buyers_count || 0),
            },
          ];

          const byCurrency = totals.by_currency || {};
          Object.keys(byCurrency).forEach(function (currency) {
            const row = byCurrency[currency];
            const parts = [];
            if (Number(row.pending || 0) > 0) {
              parts.push(
                Drupal.t('@amount pendientes', {
                  '@amount': formatMoney(row.pending, currency),
                })
              );
            }
            if (Number(row.approved || 0) > 0) {
              parts.push(
                Drupal.t('@amount aprobados', {
                  '@amount': formatMoney(row.approved, currency),
                })
              );
            }
            if (Number(row.paid || 0) > 0) {
              parts.push(
                Drupal.t('@amount pagados', {
                  '@amount': formatMoney(row.paid, currency),
                })
              );
            }
            blocks.push({
              label: Drupal.t('Ganado (@currency)', { '@currency': currency }),
              value: formatMoney(row.total, currency) +
                (parts.length ? ' · ' + parts.join(' · ') : ''),
            });
          });

          blocks.forEach(function (block) {
            const item = document.createElement('div');
            item.className = 'saibher-profile__balances-total';

            const value = document.createElement('strong');
            value.textContent = block.value;
            const label = document.createElement('span');
            label.textContent = block.label;

            item.appendChild(value);
            item.appendChild(label);
            totalsBox.appendChild(item);
          });
        }

        function renderPager(pagination, onNavigate) {
          const pages = Math.max(1, Number(pagination.pages) || 1);
          const page = Number(pagination.page) || 1;

          const pager = document.createElement('div');
          pager.className = 'saibher-profile__balances-pager';

          const prev = document.createElement('button');
          prev.type = 'button';
          prev.textContent = Drupal.t('Anterior');
          prev.disabled = page <= 1;
          prev.addEventListener('click', function () {
            onNavigate(page - 1);
          });

          const info = document.createElement('span');
          info.textContent = Drupal.t('Página @page de @pages', {
            '@page': String(page),
            '@pages': String(pages),
          });

          const next = document.createElement('button');
          next.type = 'button';
          next.textContent = Drupal.t('Siguiente');
          next.disabled = page >= pages;
          next.addEventListener('click', function () {
            onNavigate(page + 1);
          });

          pager.appendChild(prev);
          pager.appendChild(info);
          pager.appendChild(next);
          return pager;
        }

        function renderTable(rows, pagination, total) {
          const wrapper = document.createElement('div');

          const table = document.createElement('table');
          table.className = 'saibher-profile__balances-table';

          const head = document.createElement('thead');
          const headRow = document.createElement('tr');

          const columns = [
            { key: 'name', label: Drupal.t('Persona') },
            { key: 'joined', label: Drupal.t('Registrado') },
            { key: 'orders', label: Drupal.t('Órdenes'), numeric: true },
            { key: null, label: Drupal.t('Comisiones'), numeric: true },
            { key: null, label: Drupal.t('Pendiente'), numeric: true },
            { key: null, label: Drupal.t('Aprobado'), numeric: true },
            { key: null, label: Drupal.t('Pagado'), numeric: true },
            { key: 'earned', label: Drupal.t('Ganado'), numeric: true },
          ];

          columns.forEach(function (column) {
            const th = document.createElement('th');
            th.scope = 'col';
            th.textContent = column.label;

            if (column.key) {
              th.dataset.sort = column.key;
              th.classList.add('saibher-profile__balances-sortable');
              th.setAttribute('aria-sort', 'none');

              if (column.key === pagination.sort) {
                th.classList.add('saibher-profile__balances-active');
                const arrow = document.createElement('span');
                arrow.className = 'saibher-profile__balances-arrow';
                arrow.textContent = pagination.dir === 'asc' ? ' ↑' : ' ↓';
                th.appendChild(arrow);
                th.setAttribute(
                  'aria-sort',
                  pagination.dir === 'asc' ? 'ascending' : 'descending'
                );
              }

              th.addEventListener('click', function () {
                state.sort = column.key;
                state.dir =
                  pagination.sort === column.key && pagination.dir === 'desc'
                    ? 'asc'
                    : 'desc';
                state.page = 1;
                load();
              });
            }

            headRow.appendChild(th);
          });

          head.appendChild(headRow);
          table.appendChild(head);

          const body = document.createElement('tbody');
          rows.forEach(function (row) {
            const tr = document.createElement('tr');

            const nameTd = document.createElement('td');
            nameTd.textContent = row.name || '—';
            tr.appendChild(nameTd);

            const joinedTd = document.createElement('td');
            joinedTd.textContent = formatDate(row.joined);
            tr.appendChild(joinedTd);

            const ordersTd = document.createElement('td');
            ordersTd.className = 'saibher-profile__balances-numeric';
            ordersTd.textContent = String(Number(row.orders) || 0);
            tr.appendChild(ordersTd);

            const commsTd = document.createElement('td');
            commsTd.className = 'saibher-profile__balances-numeric';
            commsTd.textContent = String(Number(row.comms_count) || 0);
            tr.appendChild(commsTd);

            [row.pending, row.approved, row.paid].forEach(function (amount) {
              const currency = row.currency || '';
              const moneyTd = document.createElement('td');
              moneyTd.className = 'saibher-profile__balances-numeric';
              moneyTd.textContent =
                Number(row.earned) > 0 || Number(amount) > 0
                  ? formatMoney(amount, currency)
                  : '—';
              tr.appendChild(moneyTd);
            });

            const earnedTd = document.createElement('td');
            earnedTd.className =
              'saibher-profile__balances-numeric saibher-profile__balances-total-row';
            earnedTd.textContent =
              Number(row.earned) > 0
                ? formatMoney(row.earned, row.currency)
                : '—';
            tr.appendChild(earnedTd);

            body.appendChild(tr);
          });
          table.appendChild(body);
          wrapper.appendChild(table);

          if (total > 0 && pagination) {
            wrapper.appendChild(
              renderPager(pagination, function (page) {
                state.page = page;
                load();
              })
            );
          }

          return wrapper;
        }

        function renderEmpty(message) {
          const box = document.createElement('p');
          box.className = 'saibher-profile__balances-empty';
          box.textContent = message;
          return box;
        }

        function load() {
          if (!stateBox) {
            return;
          }

          const url = new URL(endpoint, window.location.origin);
          if (state.month) {
            url.searchParams.set('month', state.month);
          }
          url.searchParams.set('page', String(state.page));
          url.searchParams.set('sort', state.sort);
          url.searchParams.set('dir', state.dir);

          setState(Drupal.t('Cargando…'));
          stateBox.replaceChildren();

          fetch(url.toString(), {
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
            },
          })
            .then(function (response) {
              if (response.status === 403) {
                throw new Error(
                  Drupal.t('No tienes permiso para ver este panel.')
                );
              }
              return response.json().catch(function () {
                throw new Error(
                  Drupal.t('El servidor respondió de forma inesperada.')
                );
              });
            })
            .then(function (payload) {
              if (!payload || !Array.isArray(payload.rows)) {
                throw new Error(Drupal.t('Datos de balance inválidos.'));
              }

              // Sync with the server-clamped page (e.g. page > total pages).
              if (payload.pagination && Number(payload.pagination.page) >= 1) {
                state.page = Number(payload.pagination.page);
              }

              renderTotals(payload.totals || {});

              if (!monthSelect) {
                return;
              }
              // Rebuild month options, keeping the current selection.
              const current = state.month;
              monthSelect.replaceChildren();
              const all = document.createElement('option');
              all.value = '';
              all.textContent = Drupal.t('Todos');
              monthSelect.appendChild(all);
              (payload.months || []).forEach(function (m) {
                const opt = document.createElement('option');
                opt.value = m;
                opt.textContent = monthLabel(m);
                monthSelect.appendChild(opt);
              });
              monthSelect.value = current;

              const total = Number(payload.pagination.total) || 0;
              stateBox.replaceChildren();
              if (payload.rows.length === 0) {
                stateBox.appendChild(
                  renderEmpty(
                    total === 0
                      ? state.month
                        ? Drupal.t('Sin actividad para este mes.')
                        : Drupal.t('Aún no tienes referidos.')
                      : Drupal.t('No se encontraron resultados.')
                  )
                );
              }
              else {
                stateBox.appendChild(
                  renderTable(payload.rows, payload.pagination, total)
                );
              }
            })
            .catch(function (error) {
              renderTotals({ referred_count: 0, buyers_count: 0, by_currency: {} });
              stateBox.replaceChildren();
              stateBox.appendChild(renderEmpty(error.message));
            })
            .finally(function () {
              if (monthSelect) {
                monthSelect.disabled = false;
              }
            });
        }

        if (monthSelect) {
          monthSelect.addEventListener('change', function () {
            state.month = monthSelect.value;
            state.page = 1;
            load();
          });
        }

        load();
      });
    },
  };
})(Drupal, drupalSettings);