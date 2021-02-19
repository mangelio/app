<template>
  <custom-checkbox-field for-id="is-marked" :label="$t('issue.is_marked')">
    <input
        class="custom-control-input" type="checkbox" id="is-marked"
        :class="{'is-valid': fields.isMarked.dirty && !fields.isMarked.errors.length, 'is-invalid': fields.isMarked.dirty && fields.isMarked.errors.length }"
        v-model="issue.isMarked"
        :true-value="true"
        :false-value="false"
        :indeterminate.prop="issue.isMarked === null"
        @input="fields.isMarked.dirty = true"
        @change="validate('isMarked')"
    >
    <template v-slot:after>
      <div>
        <a class="btn-link clickable" v-if="fields.isMarked.dirty" @click="reset('isMarked')">
          {{ $t('form.reset') }}
        </a>
      </div>
    </template>
  </custom-checkbox-field>

  <custom-checkbox-field for-id="was-added-with-client" :label="$t('issue.was_added_with_client')">
    <input
        class="custom-control-input" type="checkbox" id="was-added-with-client"
        :class="{'is-valid': fields.wasAddedWithClient.dirty && !fields.wasAddedWithClient.errors.length, 'is-invalid': fields.wasAddedWithClient.dirty && fields.wasAddedWithClient.errors.length }"
        v-model="issue.wasAddedWithClient"
        :true-value="true"
        :false-value="false"
        :indeterminate.prop="issue.wasAddedWithClient === null"
        @input="fields.wasAddedWithClient.dirty = true"
        @change="validate('wasAddedWithClient')"
    >
    <template v-slot:after>
      <div>
        <a class="btn-link clickable" v-if="fields.wasAddedWithClient.dirty" @click="reset('wasAddedWithClient')">
          {{ $t('form.reset') }}
        </a>
      </div>
    </template>
  </custom-checkbox-field>

  <hr />

  <form-field for-id="description" :label="$t('issue.description')">
    <input id="description" class="form-control" type="text" required="required"
           :class="{'is-valid': fields.description.dirty && !fields.description.errors.length, 'is-invalid': fields.description.dirty && fields.description.errors.length }"
           v-model="issue.description"
           @change="validate('description')"
           @input="fields.description.dirty = true">
    <invalid-feedback :errors="fields.description.errors" />
    <a class="btn-link clickable" v-if="fields.description.dirty" @click="reset('description')">
      {{ $t('form.reset') }}
    </a>
  </form-field>

  <form-field for-id="craftsman" :label="$t('issue.craftsman')">
    <select class="custom-select"
            v-model="tradeFilter">
      <option :value="null">{{ $t('edit_issues_button.no_trade_filter') }}</option>
      <option disabled></option>
      <option v-for="trade in sortedTrade" :value="trade">
        {{ trade }}
      </option>
    </select>
    <select class="custom-select"
            :class="{'is-valid': fields.craftsman.dirty && !fields.craftsman.errors.length, 'is-invalid': fields.craftsman.dirty && fields.craftsman.errors.length }"
            v-model="issue.craftsman"
            @input="fields.craftsman.dirty = true"
            @change="validate('craftsman')"
    >
      <option v-if="!tradeFilter" :value="null">{{ $t('edit_issues_button.no_craftsman') }}</option>
      <option v-if="!tradeFilter" disabled></option>
      <option v-for="craftsman in sortedCraftsmen" :value="craftsman['@id']"
              :key="craftsman['@id']">
        {{ craftsman.company }} - {{ craftsman.contactName }}
      </option>
    </select>
    <invalid-feedback :errors="fields.description.errors" />
    <a class="btn-link clickable" v-if="fields.craftsman.dirty" @click="reset('craftsman')">
      {{ $t('form.reset') }}
    </a>
  </form-field>

  <form-field for-id="deadline" :label="$t('issue.deadline')">
    <span ref="deadline-anchor" />
    <flat-pickr
        id="deadline" class="form-control"
        v-model="issue.deadline"
        @input="fields.deadline.dirty = true"
        @change="validate('deadline')"
        :config="datePickerConfig">
    </flat-pickr>
    <invalid-feedback :errors="fields.deadline.errors" />
    <a class="btn-link clickable" v-if="fields.deadline.dirty" @click="reset('deadline')">
      {{ $t('form.reset') }}
    </a>
  </form-field>
</template>

<script>

import { createField, validateField, validateFields, changedFieldValues, resetFields } from '../../services/validation'
import FormField from '../Library/FormLayout/FormField'
import InvalidFeedback from '../Library/FormLayout/InvalidFeedback'
import Help from '../Library/FormLayout/Help'
import { dateConfig, flatPickr } from '../../services/flatpickr'
import CustomCheckboxField from '../Library/FormLayout/CustomCheckboxField'

export default {
  components: {
    CustomCheckboxField,
    Help,
    InvalidFeedback,
    FormField,
    flatPickr
  },
  emits: ['update'],
  data () {
    return {
      mounted: false,
      fields: {
        isMarked: createField(),
        wasAddedWithClient: createField(),
        description: createField(),
        craftsman: createField(),
        deadline: createField()
      },
      issue: {
        isMarked: null,
        wasAddedWithClient: null,
        description: null,
        craftsman: null,
        deadline: null,
      },
      tradeFilter: null,
    }
  },
  props: {
    template: {
      type: Object
    },
    craftsmen: {
      type: Array,
      required: true
    }
  },
  watch: {
    updatePayload: {
      deep: true,
      handler: function () {
        if (this.mounted) {
          this.$emit('update', this.updatePayload)
        }
      }
    },
    template: function () {
      this.setIssueFromTemplate()
    },
    sortedCraftsmen: function () {
      if (this.sortedCraftsmen.length === 1) {
        this.issue.craftsman = this.sortedCraftsmen[0]['@id']
        this.fields.craftsman.dirty = true
      }
    },
    'fields.deadline.dirty': function () {
      if (!this.$refs['deadline-anchor']) {
        return
      }

      const visibleInput = this.$refs['deadline-anchor'].parentElement.childNodes[4]
      if (this.fields.deadline.dirty) {
        visibleInput.classList.add('is-valid')
      } else {
        visibleInput.classList.remove('is-valid')
      }
    }
  },
  methods: {
    validate: function (field) {
      validateField(this.fields[field], this.issue[field])
    },
    reset: function (field) {
      this.fields[field].dirty = false
      this.issue[field] = this.template[field]
    },
    setIssueFromTemplate: function () {
      this.issue = Object.assign({}, this.template)
      if (this.issue.craftsman) {
        this.tradeFilter = this.craftsmen.find(c => c['@id'] === this.issue.craftsman).trade
      } else {
        this.tradeFilter = null
      }

      this.$nextTick(() => {
        resetFields(this.fields)
      })
    },
  },
  computed: {
    datePickerConfig: function () {
      return dateConfig
    },
    selectableCraftsmen: function () {
      return this.craftsmen.filter(c => !c.isDeleted)
    },
    sortedCraftsmen: function () {
      let selectableCraftsmen = this.selectableCraftsmen
      if (this.tradeFilter) {
        selectableCraftsmen = selectableCraftsmen.filter(c => c.trade === this.tradeFilter)
      }

      return selectableCraftsmen.sort((a, b) => a.company.localeCompare(b.company))
    },
    sortedTrade: function () {
      const tradeSet = new Set(this.selectableCraftsmen.map(c => c.trade))

      return Array.from(tradeSet).sort()
    },
    updatePayload: function () {
      if (this.fields.isMarked.errors.length ||
          this.fields.wasAddedWithClient.errors.length ||
          this.fields.description.errors.length ||
          this.fields.craftsman.errors.length ||
          this.fields.deadline.errors.length) {
        return null
      }

      const values = changedFieldValues(this.fields, this.issue, this.template)

      // ensure empty string is null
      if (Object.prototype.hasOwnProperty.call(values, 'deadline')) {
        values.deadline = values.deadline ? values.deadline : null
      }

      return values
    },
  },
  mounted () {
    this.setIssueFromTemplate()
    validateFields(this.fields, this.issue)

    this.mounted = true
    this.$emit('update', this.updatePayload)
  }
}
</script>