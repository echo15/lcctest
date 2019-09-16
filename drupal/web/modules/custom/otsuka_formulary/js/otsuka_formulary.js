/**
 * @file
 */

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.insurancefinder = {
    attach: function (context, settings) {
      $('.formulary-modal').hide();
      $('#edit-coverage-type, #edit-state').change(function () {
        $('.formulary-modal').show();
      });
      // Disable submit button if state not selected.
      if (($('#insuranceformdata-form #edit-state').val() == "") || ($('#insuranceformdata-form #edit-coverage-type').val() == "") || ($('#insuranceformdata-form #healthplan-select-data').length && $('#insuranceformdata-form #healthplan-select-data').val() == "")) {
        $('#insuranceformdata-form .insurance-select-data').attr('disabled', 'disabled');
      }
      else {
        $('#insuranceformdata-form .insurance-select-data').removeAttr('disabled');
      }
      $('#insuranceformdata-form #healthplan-select-data').change(function () {
        // Disable submit button if state not selected.
        if (($('#insuranceformdata-form #edit-state').val() == "") || ($('#insuranceformdata-form #edit-coverage-type').val() == "") || ($('#insuranceformdata-form #healthplan-select-data').val() == "")) {
          $('#insuranceformdata-form .insurance-select-data').attr('disabled', 'disabled');
        }
        else {
          $('#insuranceformdata-form .insurance-select-data').removeAttr('disabled');
        }
      });
    }
  }

  Drupal.behaviors.mmitinsurance = {
    attach: function (context, settings) {
      $('.formulary-modal').hide();
      $('#edit-mmit-channel-type, #edit-mmitstate').change(function () {
        $('.formulary-modal').show();
      });
      // Disable submit button if state not selected.
      if (($('#mmitinsuranceformdata-form #edit-mmitstate').val() == "") || ($('#mmitinsuranceformdata-form #edit-mmit-channel-type').val() == "") || ($('#mmitinsuranceformdata-form #mmithealthplan-select-data').length && $('#mmitinsuranceformdata-form #mmithealthplan-select-data').val() == "")) {
        $('#mmitinsuranceformdata-form .mmitinsurance-select-data').attr('disabled', 'disabled');
      }
      else {
        $('#mmitinsuranceformdata-form .mmitinsurance-select-data').removeAttr('disabled');
      }
      $('#mmitinsuranceformdata-form #mmithealthplan-select-data').change(function () {
        if (($('#mmitinsuranceformdata-form #edit-mmitstate').val() == "") || ($('#mmitinsuranceformdata-form #edit-mmit-channel-type').val() == "") || ($('#mmitinsuranceformdata-form #mmithealthplan-select-data').val() == "")) {
          $('#mmitinsuranceformdata-form .mmitinsurance-select-data').attr('disabled', 'disabled');
        }
        else {
          $('#mmitinsuranceformdata-form .mmitinsurance-select-data').removeAttr('disabled');
        }
      });
    }
  }
  $(document).ready(function () {
    $('body').append('<div class="formulary-modal"></div>');
    $('.formulary-modal').css('background-image', 'url("' + drupalSettings.data.otsuka_formulary.formulary_loading_image + '")');
    $('body').find('.insurance-select-data').click(function (evt) {
        evt.preventDefault();
        evt.stopPropagation();
        var healthPlan = $('select[id=healthplan-select-data]').val();
        var coverageType = $('select[id=edit-coverage-type]').val();
        var state = $('select[id=edit-state]').val();
        var data = {
          healthPlan: healthPlan,
          coverageType: coverageType,
          state: state,
        };
        $.ajax({
          url: Drupal.url('insurancedata'),
          type: "POST",
          data: JSON.stringify(data),
          contentType: "application/json; charset=utf-8",
          dataType: "json",
          beforeSend: function () {
            $("#product_id").html('<option> Loading ...</option>');
            $(".insurance-select-data").prop('disabled', true); // Disable button.
            $('select[id=healthplan-select-data]').prop('disabled', true);
            $('select[id=edit-coverage-type]').prop('disabled', true);
            $('select[id=edit-state]').prop('disabled', true);
            $('.formulary-modal').show();
          },
          success: function (response) {
            $('#insurance-wrapper').html(response);
            $(".insurance-select-data").prop('disabled', false);
            $('select[id=healthplan-select-data]').prop('disabled', false);
            $('select[id=edit-coverage-type]').prop('disabled', false);
            $('select[id=edit-state]').prop('disabled', false);
            $(".formulary-info-content").children().removeClass('hidden');
            $('.formulary-modal').hide();
            Drupal.attachBehaviors($('#insurance-wrapper'));
          }
        });
      });
      $('body').find('.mmitinsurance-select-data').click(function (evt) {
        evt.preventDefault();
        evt.stopPropagation();
        var mmitCoverageTypeName = $('.mmitinsurance-coverage-type option:selected').text();
        var mmitHealthPlanID = $('select[id=mmithealthplan-select-data]').val();
        var mmitHealthPlanName = $('#mmithealthplan-select-data option:selected').text();
        var mmitStateName = $('#edit-mmitstate option:selected').text();
        var mmitBrandName = $('#brand_name').val();
        var data = {
          mmitCoverageTypeName: mmitCoverageTypeName,
          mmitHealthPlanID: mmitHealthPlanID,
          mmitHealthPlanName: mmitHealthPlanName,
          mmitStateName: mmitStateName,
          mmitBrandName: mmitBrandName,
        };
        $.ajax({
          url: Drupal.url('mmitinsurancedata'),
          type: "POST",
          data: JSON.stringify(data),
          contentType: "application/json; charset=utf-8",
          dataType: "json",
          beforeSend: function () {
            $(".mmitinsurance-select-data").prop('disabled', true); // Disable button.
            $('select[id=edit-mmiy-coverage-type]').prop('disabled', true);
            $('select[id=edit-mmitstate]').prop('disabled', true);
            $('select[id=mmithealthplan-select-data]').prop('disabled', true);
            $('.formulary-modal').show();
          },
          success: function (response) {
            $('#insurance-wrapper').html(response);
            $(".mmitinsurance-select-data").prop('disabled', false); // Enable button.
            $('select[id=edit-mmiy-coverage-type]').prop('disabled', false);
            $('select[id=edit-mmitstate]').prop('disabled', false);
            $('select[id=mmithealthplan-select-data]').prop('disabled', false);
            $(".formulary-info-content").children().removeClass('hidden');
            $('#insurance-wrapper .field-label.brand-name').html($('#brand_full_name_email_email').val() + ' ' + Drupal.t('Status') + ':'); // Update Brand Name.
            $('.formulary-modal').hide();
            Drupal.attachBehaviors($('#insurance-wrapper'));
          }
        });
      });
  });
})(jQuery, Drupal);
