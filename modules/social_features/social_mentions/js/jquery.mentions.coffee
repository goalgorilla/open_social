(($) ->

  "use strict"

  $.widget("ui.mentionsAutocomplete", $.ui.autocomplete,
    options: $.extend({}, $.ui.autocomplete.prototype.options,
      messages:
        noResults: ""
    )

    _create: ->
      @overriden =
        select: @options.select
        focus: @options.focus
        change: @options.change

      @options.select = $.proxy @selectCallback, @
      @options.focus = $.proxy @focusCallback, @
      @options.change = $.proxy @changeCallback, @
      $.ui.autocomplete.prototype._create.call @
      @liveRegion.remove()
      return

    search: (value, event) ->
      if not value
        return false

      $.ui.autocomplete.prototype.search.call @, value, event

    selectCallback: (event, ui) ->
      if @overriden.select
        return @overriden.select event, ui

      false

    focusCallback: (event, ui) ->
      if @overriden.focus
        return @overriden.focus event, ui

      false

    changeCallback: (event, ui) ->
      if @overriden.change
        return @overriden.change event, ui

      false

    _value: ->
      false

    _renderItem: (ul, item) ->
      if typeof @options.renderItem is "function"
        return @options.renderItem ul, item

      $.ui.autocomplete.prototype._renderItem.call @, ul, item
  )

  class MentionsHandlerBase
    constructor: (@mentions) ->
      @element = $(@mentions.element)
      @cache = {
        mentions: []
        value:
          original: ""
          compiled: ""
      }

    initEvents: ->
      throw "initEvents method is not implemented"

    setValue: (value) ->
      throw "setValue method is not implemented"

  class MentionsInput extends MentionsHandlerBase
    constructor: (@mentions) ->
      super(@mentions)
      @createHiddenField()

      @element.mentionsAutocomplete jQuery.extend({},
        select: (event, ui) => @onSelect event, ui
        appendTo: @element.parent()
      , @mentions.settings.autocomplete)

    initEvents: ->
      @element.on "input", (event) => @handleInput event

    handleInput: () ->
      position = @element.caret("pos")
      value = @element.val().substring 0, position

      @refreshMentions @cache.value.compiled, @compile @element.val()

      @cache.value =
        original: @decompile @element.val()
        compiled: @compile @element.val()

      @updateValues()

      match1 = /(\S+)$/g.exec value

      if not match1
        @element.mentionsAutocomplete "close"
        return

      trigger = @mentions.settings.trigger
      match2 = new RegExp("(?:^|\s)[" + trigger + "]([^" + trigger + "]{" + @mentions.settings.length.join(",") + "})$").exec match1[0]

      if not match2
        @element.mentionsAutocomplete "close"
        return

      @start = match1.index
      @end = @start + match2[0].length
      query = match2[1]

      if @timer
        window.clearTimeout @timer

      @timer = window.setTimeout =>
        @mentions.fetchData query, (response) =>
          @element.mentionsAutocomplete "option", "source", (req, add) ->
            add(response)
          @element.mentionsAutocomplete "search", query
      , @mentions.settings.delay

    createHiddenField: ->
      @hidden = $ "<input />",
        type: "hidden"
        name: @element.attr "name"
      @element
        .after @hidden
        .removeAttr "name"

    updateValues: ->
      @hidden.val @cache.value.original
      @element.val @cache.value.compiled

    compile: (value) ->
      result = ""
      position = 0

      for mention in @cache.mentions
        result += value.substring position, mention.start.original
        piece = value.substring mention.start.original, mention.start.original + mention.value.original.length
        position += value.substring(position, mention.start.original).length

        if mention.value.original == piece
          result += mention.value.compiled
          position += piece.length

      return result + value.substring position

    decompile: (value) ->
      result = ""
      position = 0

      for mention in @cache.mentions
        result += value.substring position, mention.start.compiled
        piece = value.substring mention.start.compiled, mention.start.compiled + mention.value.compiled.length
        position += value.substring(position, mention.start.compiled).length

        if mention.value.compiled == piece
          result += mention.value.original
          position += piece.length

      return result + value.substring position

    refreshMentions: (oldValue, newValue) ->
      position = 0

      if newValue
        value = newValue
      else
        value = @cache.value.compiled

      if oldValue and newValue
        diff = JsDiff.diffChars oldValue, newValue
        cursor = 0

        setPosition = (cursor, delta) =>
          for mention, key in @cache.mentions
            if mention.start.compiled >= cursor
              @cache.mentions[key].start.compiled += delta

        for item in diff
          if item.added
            setPosition cursor, item.count
          else if item.removed
            setPosition cursor, -item.count

          if not item.removed
            cursor += item.count

      for mention, key in @cache.mentions
        piece = value.substring mention.start.compiled, mention.start.compiled + mention.value.compiled.length
        position = value.substring(position, mention.start.compiled).length

        if mention.value.compiled == piece
          @cache.mentions[key].start.original = position
          position += piece.length
        else
          @cache.mentions.splice key, 1

    onSelect: (event, ui) ->
      before = @cache.value.compiled.substring 0, @start
      after = @cache.value.compiled.substring @end
      mention = @mentions.settings.template(ui.item);

      start =
        original: 0
        compiled: @start
      value =
        original: @mentions.settings.markup(ui.item)
        compiled: mention

      @cache.mentions.push {
        start,
        value
      }

      value = before + mention + @mentions.settings.suffix + after

      @cache.value.compiled = value
      @refreshMentions()
      @cache.value.original = @decompile value
      @updateValues()

    setValue: (value) ->
      @cache.value =
        original: @decompile value
        compiled: @compile value
      @updateValues()
      @refreshMentions()

  class MentionsCKEditor extends MentionsHandlerBase
    constructor: (@mentions) ->
      super(@mentions)
      @createHiddenField()

      @editor = CKEDITOR.instances[@element.attr("id")]
      @mentions.settings.suffix = @mentions.settings.suffix.replace " ", "\u00A0"

      editor = @editor
      element = @element
      mentions = @mentions

      @element.mentionsAutocomplete jQuery.extend({}, @mentions.settings.autocomplete,
        select: (event, ui) => @onSelect event, ui
        appendTo: @element.parent()
        open: (event, ui) ->
          position = $(editor.document.$.body).caret("position", iframe: editor.window.$.frameElement)
          offset = $(editor.document.$.body).caret("offset", iframe: editor.window.$.frameElement)
          top = 5 + position.height + position.top + $(editor.ui.space("top").$).outerHeight(true) + offset.height;

          element.data("ui-mentionsAutocomplete").menu.element.css(
            left: 0
            top: top
          )

          if mentions.settings.autocomplete.open
            mentions.settings.autocomplete.open.call @, event, ui
      )

    initEvents: ->
      @editor.on "change", () => @handleInput()
      $(@editor.window.$.document.body).on "click", () =>
        @element.mentionsAutocomplete "close"

    handleInput: ->
      @refreshMentions()
      @updateValues()

      selection = @editor.window.$.getSelection()
      node = selection.focusNode
      value = node.textContent
      position = selection.focusOffset

      value = value.substring 0, position
      match1 = /(\S+)$/g.exec value

      if @timer
        window.clearTimeout @timer

      if not match1
        @element.mentionsAutocomplete "close"
        return

      trigger = @mentions.settings.trigger
      match2 = new RegExp("(?:^|\s)[" + trigger + "]([^" + trigger + "]{" + @mentions.settings.length.join(",") + "})$").exec match1[0]

      if not match2
        @element.mentionsAutocomplete "close"
        return

      @start = match1.index
      @end = @start + match2[0].length
      query = match2[1]

      @timer = window.setTimeout =>
        @mentions.fetchData query, (response) =>
          @element.mentionsAutocomplete "option", "source", (req, add) ->
            add(response)
          @element.mentionsAutocomplete "search", query
      , @mentions.settings.delay

    createHiddenField: ->
      @hidden = $ "<input />",
        type: "hidden"
        name: @element.attr "name"
      @element
        .after @hidden
        .removeAttr "name"

    updateValues: ->
      @hidden.val @getValue()

    refreshMentions: ->
      for mention, key in @cache.mentions
        if mention.$node.html() != @mentions.settings.template mention.item
          @cache.mentions.splice key, 1

    onSelect: (event, ui) ->
      _id = Math.random().toString().split(".")[1];
      mention = @mentions.settings.template ui.item
      position =
        start: @start
        end: @end
      $node = $ "<mention />",
        id: _id
        .html mention
        .data "mentionItem", ui.item

      ui.item._id = _id
      @insertMention $node, position, ui.item
      @updateValues()
      @editor.focus()

    insertMention: ($node, position, item) ->
      selection = @editor.window.$.getSelection()
      node = selection.focusNode
      range = selection.getRangeAt 0
      range.setStart node, position.start
      range.setEnd node, position.end
      range.deleteContents()

      if @mentions.settings.suffix
        suffix = document.createTextNode @mentions.settings.suffix
        range.insertNode $node.get 0
        $node.after suffix
        range.setStartAfter suffix
      else
        range.insertNode $node.get 0

      @cache.mentions.push {
        position
        item
        $node
      }

      range.collapse true
      selection.removeAllRanges()
      selection.addRange range

    getValue: ->
      $container = $ @editor.document.$.body.cloneNode true

      for mention in @cache.mentions
        markup = @mentions.settings.markup mention.item
        $("mention#" + mention.item._id, $container).before(markup).remove()

      $container.html()

    setValue: (value) ->
      @editor.setData value
      @updateValues()
      @refreshMentions()

  class Mentions
    constructor: (@element, settings) ->
      @settings = $.extend({},
        trigger: "@"
        suffix: " "
        delay: 200
        source: []
        length: [1, 20]
        autocomplete: {}
        markup: (item) ->
          return "[~" + item.value + "]"
        template: (item) ->
          return item.label;
      , settings)

      $(@element).wrap(
        $("<div />",
          class: "mentions-input"
        )
      )

      if window.CKEDITOR and window.CKEDITOR.instances[@element.id]
        @handler = new MentionsCKEditor(@)
      else if @element.tagName in ["INPUT", "TEXTAREA"]
        @handler = new MentionsInput(@)
      else
        throw "Element " + @element.tagName + " is not supported"

      @handler.initEvents()

    fetchData: (query, callback) ->
      if typeof @settings.source is "object"
        callback @settings.source
      else if typeof @settings.source is "string"
        $.getJSON(@settings.source, {term: query}, (response) ->
          callback response
        )

  $.fn.extend(
    mentionsInput: (settings) ->
      this.each((i, e) ->
        $(e).data "mentionsInput", new Mentions(e, settings)
      )
  )

) jQuery