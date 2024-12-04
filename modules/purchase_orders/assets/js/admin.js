$(function () {
	let table_purchase_orders = $("table.table-purchase_orders");

	if (table_purchase_orders.length > 0) {
		var Sales_table_ServerParams = {};
		var Sales_table_Filter = $("._hidden_inputs._filters input");

		$.each(Sales_table_Filter, function () {
			Sales_table_ServerParams[$(this).attr("name")] =
				'[name="' + $(this).attr("name") + '"]';
		});

		if (table_purchase_orders.length) {
			// Purchase orders table
			initDataTable(
				table_purchase_orders,
				admin_url + "purchase_orders/table",
				"undefined",
				"undefined",
				Sales_table_ServerParams,
				[
					[3, "desc"],
					[0, "desc"],
				]
			);
		}
	}

	// Make items sortable
	init_purchase_orders_total();

	if ($("body").hasClass("purchase_orders-pipeline")) {
		var purchase_orderid = $('input[name="purchase_orderid"]').val();
		purchase_order_pipeline_open(purchase_orderid);
	}

	// add note
	$("body").on("submit", ".purchase_order-notes-form", function () {
		var form = $(this);
		if (form.find('textarea[name="description"]').val() === "") {
			return;
		}

		$.post(form.attr("action"), $(form).serialize()).done(function (
			rel_id
		) {
			// Reset the note textarea value
			form.find('textarea[name="description"]').val("");
			// Reload the notes
			let controller = form.attr("data-controller");
			if (controller?.length) {
				get_sales_notes(rel_id, controller);
			}
		});
		return false;
	});

	$("body").on(
		"change",
		'div.purchase_order input[name="date"]',
		function () {
			var date = $(this).val();
			do_prefix_year(date);

			// This function not work on edit
			if ($('input[name="isedit"]').length > 0) {
				return;
			}

			if ($("body").find("div.purchase_order").length > 0) {
				due_calc_url = admin_url + "purchase_orders/get_due_date";
				due_date_input_name = "expirydate";

				if (date === "") {
					$('input[name="' + due_date_input_name + '"]').val("");
				}

				if (date !== "") {
					$.post(due_calc_url, {
						date: date,
					}).done(function (formatted) {
						if (formatted) {
							$('input[name="' + due_date_input_name + '"]').val(
								formatted
							);
						}
					});
				}
			}
		}
	);

	// Show send to email purchase_order modal
	$("body").on("click", ".purchase-order-send-to-client", function (e) {
		e.preventDefault();
		$("#purchase_order_send_to_client_modal").modal("show");
	});

	// Send templaate modal custom close function causing problems if is on pipeline view
	$("body").on("click", ".close-send-template-modal", function () {
		$("#purchase_order_send_to_client_modal").modal("hide");
	});
});

// Init single purchase_order
function init_purchase_order(id) {
	load_small_table_item(
		id,
		"#purchase_order",
		"purchase_orderid",
		"purchase_orders/get_purchase_order_data_ajax",
		".table-purchase_orders"
	);
}

function schedule_purchase_order_send(id) {
	$("#purchase_order").load(
		admin_url + "email_schedule_purchase_order/create/" + id
	);
}

function edit_purchase_order_scheduled_email(schedule_id) {
	$("#purchase_order").load(
		admin_url + "email_schedule_purchase_order/edit/" + schedule_id
	);
}
// Delete purchase_order attachment
function delete_purchase_order_attachment(id) {
	if (confirm_delete()) {
		requestGet("purchase_orders/delete_attachment/" + id)
			.done(function (success) {
				if (success == 1) {
					$("body")
						.find('[data-attachment-id="' + id + '"]')
						.remove();
					var rel_id = $("body")
						.find('input[name="_attachment_sale_id"]')
						.val();
					$("body").hasClass("purchase_orders-pipeline")
						? purchase_order_pipeline_open(rel_id)
						: init_purchase_order(rel_id);
				}
			})
			.fail(function (error) {
				alert_float("danger", error.responseText);
			});
	}
}

// Purchase orders quick total stats
function init_purchase_orders_total(manual) {
	if ($("#purchase_orders_total").length === 0) {
		return;
	}
	var _est_total_href_manual = $(".purchase_orders-total");
	if (
		$("body").hasClass("purchase_orders-total-manual") &&
		typeof manual == "undefined" &&
		!_est_total_href_manual.hasClass("initialized")
	) {
		return;
	}
	_est_total_href_manual.addClass("initialized");
	var currency = $("body").find('select[name="total_currency"]').val();
	var _years = $("body")
		.find('select[name="purchase_orders_total_years"]')
		.selectpicker("val");
	var years = [];
	$.each(_years, function (i, _y) {
		if (_y !== "") {
			years.push(_y);
		}
	});

	var customer_id = "";
	var project_id = "";

	var _customer_id = $('.customer_profile input[name="userid"]').val();
	var _project_id = $('input[name="project_id"]').val();
	if (typeof _customer_id != "undefined") {
		customer_id = _customer_id;
	} else if (typeof _project_id != "undefined") {
		project_id = _project_id;
	}

	$.post(admin_url + "purchase_orders/get_purchase_orders_total", {
		currency: currency,
		init_total: true,
		years: years,
		customer_id: customer_id,
		project_id: project_id,
	}).done(function (response) {
		$("#purchase_orders_total").html(response);
	});
}

// Validates purchase_order add/edit form
function validate_purchase_order_form(selector) {
	selector =
		typeof selector == "undefined" ? "#purchase_order-form" : selector;

	appValidateForm($(selector), {
		clientid: {
			required: {
				depends: function () {
					var customerRemoved =
						$("select#clientid").hasClass("customer-removed");
					return !customerRemoved;
				},
			},
		},
		date: "required",
		currency: "required",
		number: {
			required: true,
		},
	});

	$("body")
		.find('input[name="number"]')
		.rules("add", {
			remote: {
				url:
					admin_url +
					"purchase_orders/validate_purchase_order_number",
				type: "post",
				data: {
					number: function () {
						return $('input[name="number"]').val();
					},
					isedit: function () {
						return $('input[name="number"]').data("isedit");
					},
					original_number: function () {
						return $('input[name="number"]').data(
							"original-number"
						);
					},
					date: function () {
						return $("body")
							.find('.purchase_order input[name="date"]')
							.val();
					},
				},
			},
			messages: {
				remote: app.lang.purchase_order_number_exists,
			},
		});
}

// Sort purchase_orders in the pipeline view / switching sort type by click
function purchase_orders_pipeline_sort(type) {
	kan_ban_sort(type, purchase_order_pipeline);
}

// Init purchase_orders pipeline
function purchase_order_pipeline() {
	init_kanban(
		"purchase_orders/get_pipeline",
		purchase_orders_pipeline_update,
		".pipeline-status",
		290,
		360
	);
}

// Used when purchase_order is updated from pipeline. eq changed order or moved to another status
function purchase_orders_pipeline_update(ui, object) {
	if (object === ui.item.parent()[0]) {
		var data = {
			purchase_orderid: $(ui.item).attr("data-purchase_order-id"),
			status: $(ui.item.parent()[0]).attr("data-status-id"),
			order: [],
		};

		$.each(
			$(ui.item).parents(".pipeline-status").find("li"),
			function (idx, el) {
				var id = $(el).attr("data-purchase_order-id");
				if (id) {
					data.order.push([id, idx + 1]);
				}
			}
		);

		check_kanban_empty_col("[data-purchase_order-id]");

		setTimeout(function () {
			$.post(admin_url + "purchase_orders/update_pipeline", data).done(
				function (response) {
					update_kan_ban_total_when_moving(ui, data.status);
					purchase_order_pipeline();
				}
			);
		}, 200);
	}
}

// Purchase order single open in pipeline
function purchase_order_pipeline_open(id) {
	if (id === "") {
		return;
	}
	requestGet("purchase_orders/pipeline_open/" + id).done(function (response) {
		var visible = $(".purchase_order-pipeline:visible").length > 0;
		$("#purchase_order").html(response);
		if (!visible) {
			$(".purchase_order-pipeline").modal({
				show: true,
				backdrop: "static",
				keyboard: false,
			});
		} else {
			$("#purchase_order")
				.find(".modal.purchase_order-pipeline")
				.removeClass("fade")
				.addClass("in")
				.css("display", "block");
		}
	});
}
