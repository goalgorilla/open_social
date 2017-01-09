(($) ->

  "use strict"

  namespace = "mentionsInput"

  Selection =
    get: (input) ->
      start: input[0].selectionStart,
      end: input[0].selectionEnd

    set: (input, start, end=start) ->
      if input[0].selectionStart
        input[0].selectStart = start
        input[0].selectionEnd = end

  entityMap =
    "&": "&amp;"
    "<": "&lt;"
    ">": "&gt;"
    "\"": "&quot;"
    "'": "&#39;"
    "/": "&#x2F;"


  escapeHtml = (text) ->
    text.replace /[&<>"'\/]/g, (s) ->
      entityMap[s]

  escapeRegExp = (str) ->
    specials = /[.*+?|()\[\]{}\\$^]/g # .*+?|()[]{}\$^
    return str.replace(specials, "\\$&")


  $.widget( "ui.areacomplete", $.ui.autocomplete,
    options: $.extend({}, $.ui.autocomplete.prototype.options,
      matcher: "(\\b[^,]*)",
      suffix: ''
    )

    _create: ->
      @overriden =
        select: @options.select
        focus: @options.focus
      @options.select = $.proxy(@selectCallback, @)
      @options.focus = $.proxy(@focusCallback, @)

      $.ui.autocomplete.prototype._create.call(@)
      @matcher = new RegExp(@options.matcher + '$')

    selectCallback: (event, ui) ->
      value = @_value()
      before = value.substring(0, @start)
      after = value.substring(@end)
      newval = ui.item.value + @options.suffix
      value = before + newval + after
      @_value(value)
      Selection.set(@element, before.length + newval.length)

      if @overriden.select
        ui.item.pos = @start
        @overriden.select(event, ui)
      return false

    focusCallback: ->
      if @overriden.focus
        return @overriden.focus(event, ui)
      return false

    search: (value, event) ->
      if not value
        value = @_value()
        pos = Selection.get(@element).start
        value = value.substring(0, pos)
        match = @matcher.exec(value)

        if not match
          return ''

        whitespace = /^\s/.exec match[0]

        if whitespace and whitespace[0]
          match.index++

        @start = match.index
        @end = match.index + match[0].length
        @searchTerm = match[1]
      return $.ui.autocomplete.prototype.search.call(@, @searchTerm, event)

    _renderItem: (ul, item) ->
      if typeof @options.renderItem is 'function'
        return @options.renderItem ul, item

      li = $('<li>')
      anchor = $('<a>').appendTo(li)
      if item.image
        anchor.append("<img src=\"#{item.image}\" />")

      regexp = new RegExp("(" + escapeRegExp(this.searchTerm) + ")", "gi");
      value = item.value.replace(regexp, "<strong>$&</strong>")
      anchor.append(value)
      return li.appendTo(ul)
  )


  $.widget( "ui.editablecomplete", $.ui.areacomplete,
    options: $.extend({}, $.ui.areacomplete.prototype.options,
      showAtCaret: false
    )

    selectCallback: (event, ui) ->
      pos = {start: @start, end: @end}
      if @overriden.select
        ui.item.pos = pos
        if @overriden.select(event, ui) == false
          return false

      mention = document.createTextNode ui.item.value
      insertMention mention, pos, @options.suffix
      @element.change()
      return false

    search: (value, event) ->
      if not value
        sel = window.getSelection()
        node = sel.focusNode
        value = node.textContent
        pos = sel.focusOffset
        value = value.substring(0, pos)
        match = @matcher.exec(value)
        if not match
          return ''

        @start = match.index
        @end = match.index + match[0].length
        @_setDropdownPosition node
        @searchTerm = match[1]
      return $.ui.autocomplete.prototype.search.call(@, @searchTerm, event)

    _setDropdownPosition: (node) ->
      if @options.showAtCaret
        boundary = document.createRange()
        boundary.setStart node, @start
        boundary.collapse true
        rect = boundary.getClientRects()[0]
        posX = rect.left + (window.scrollX || window.pageXOffset)
        posY = rect.top + rect.height + (window.scrollY || window.pageYOffset)
        @options.position.of = document
        @options.position.at = "left+#{posX} top+#{posY}"
  )


  class MentionsBase
    marker: '\u200B',

    constructor: (@input, options) ->
      @options = $.extend({}, @settings, options)
      if not @options.source
        @options.source = @input.data('source') or []

    _getMatcher: ->
      allowedChars = '[^' + @options.trigger + ']'
      return '(?:^|\\s)[' + @options.trigger + '](' + allowedChars + '{0,20})'

    _markupMention: (mention) ->
      return "@[#{mention.value}](#{mention.uid})"


  class MentionsInput extends MentionsBase
    mimicProperties = [
      'backgroundColor', 'marginTop', 'marginBottom', 'marginLeft', 'marginRight',
      'paddingTop', 'paddingBottom', 'paddingLeft', 'paddingRight',
      'borderTopWidth', 'borderLeftWidth', 'borderBottomWidth', 'borderRightWidth',
      'fontSize', 'fontStyle', 'fontFamily', 'fontWeight', 'lineHeight', 'height', 'boxSizing'
    ]

    constructor: (@input, options) ->
      @settings =
        trigger: '@',
        widget: 'areacomplete',
        suffix: '',
        markup: @_markupMention,
        preview: true,
        autocomplete: {
          autoFocus: true,
          delay: 0
        }

      super @input, options

      @mentions = []
      @input.addClass('input')

      container = $('<div>', {'class': 'mentions-input'})
      container.css('display', @input.css('display'))
      @container = @input.wrapAll(container).parent()

      @hidden = @_createHidden()

      if @options.preview
        @highlighter = @_createHighlighter()
        @highlighterContent = $('div', @highlighter)

        @input.focus(=>
          @highlighter.addClass('focus')
        ).blur(=>
          @highlighter.removeClass('focus')
        )

      options = $.extend(
        matcher: @_getMatcher(),
        select: @_onSelect,
        suffix: @options.suffix,
        source: @options.source,
        appendTo: @input.parent()
      , @options.autocomplete)
      @autocomplete = @input[@options.widget](options)

      @_setValue(@input.val())
      @_initEvents()

    _initEvents: ->
      @input.on("input.#{namespace} change.#{namespace}", @_update)

      tagName = @input.prop("tagName")
      if tagName == "INPUT" and @options.preview
        @input.on("focus.#{namespace}", =>
          @interval = setInterval(@_updateHScroll, 10)
        )
        @input.on("blur.#{namespace}", =>
          setTimeout(@_updateHScroll, 10)
          clearInterval(@interval)
        )
      else if tagName == "TEXTAREA" and @options.preview
        @input.on("scroll.#{namespace}", (=> setTimeout(@_updateVScroll, 10)))
        @input.on("resize.#{namespace}", (=> setTimeout(@_updateVScroll, 10)))

    _setValue: (value) ->
      offset = 0
      mentionRE = /@\[([^\]]+)\]\(([^ \)]+)\)/g
      @value = value.replace(mentionRE, '$1')
      @input.val(@value)

      match = mentionRE.exec(value)
      while match
        @_addMention(
          name: match[1],
          uid: match[2],
          pos: match.index - offset
        )
        offset += match[2].length + 5
        match = mentionRE.exec(value)
      @_updateValue()

    _createHidden: ->
      hidden = $('<input>', {type: 'hidden', name: @input.attr('name')})
      hidden.appendTo(@container)
      @input.removeAttr('name')
      return hidden

    _createHighlighter: ->
      highlighter = $('<div>', {'class': 'highlighter'})

      if @input.prop("tagName") == "INPUT"
        highlighter.css('whiteSpace', 'pre')
      else
        highlighter.css('whiteSpace', 'pre-wrap')
        highlighter.css('wordWrap', 'break-word')

      content = $('<div>', {'class': 'highlighter-content'})
      highlighter.append(content).prependTo(@container)

      for property in mimicProperties
        highlighter.css property, @input.css(property)
      @input.css 'backgroundColor', 'transparent'
      return highlighter

    _update: =>
      @_updateMentions()
      @_updateValue()

    _updateMentions: =>
      value = @input.val()
      diff = diffChars(@value, value)

      update_pos = (cursor, delta) =>
        for mention in @mentions
          if mention.pos >= cursor
            mention.pos += delta

      cursor = 0
      for change in diff
        if change.added
          update_pos(cursor, change.count)
        else if change.removed
          update_pos(cursor, -change.count)
        if not change.removed
          cursor += change.count

      for mention, i in @mentions[..] by -1
        piece = value.substring(mention.pos, mention.pos + mention.value.length)
        if mention.value != piece
          @mentions.splice(i, 1)
      @value = value

    _addMention: (mention) =>
      @mentions.push(mention)
      @mentions.sort (a, b) ->
        return a.pos - b.pos

    _onSelect: (event, ui) =>
      @_updateMentions()
      @_addMention(ui.item)
      @_updateValue()

    _updateValue: =>
      value = @input.val()
      hlContent = []
      hdContent = []
      cursor = 0

      for mention in @mentions
        piece = value.substring(cursor, mention.pos)
        hlContent.push(escapeHtml(piece))
        hdContent.push(piece)

        hlContent.push("<strong>#{mention.value}</strong>")
        hdContent.push(@options.markup(mention))

        cursor = mention.pos + mention.value.length

      piece = value.substring(cursor)

      if @options.preview
        @highlighterContent.html(hlContent.join('') + escapeHtml(piece))
      @hidden.val(hdContent.join('') + piece)

    _updateVScroll: =>
      scrollTop = @input.scrollTop()
      @highlighterContent.css(top: "-#{scrollTop}px")
      @highlighter.height(@input.height())

    _updateHScroll: =>
      scrollLeft = @input.scrollLeft()
      @highlighterContent.css(left: "-#{scrollLeft}px")

    _replaceWithSpaces: (value, what) ->
      return value.replace(what, Array(what.length).join(' '))

    _cutChar: (value, index) ->
      return value.substring(0, index) + value.substring(index + 1)

    setValue: (pieces...) ->
      value = ''
      for piece in pieces
        if typeof piece == 'string'
          value += piece
        else
          value += @options.markup(piece)
      @_setValue(value)

    getValue: ->
      return @hidden.val()

    getRawValue: ->
      return @input.val().replace(@marker, '')

    getMentions: ->
      return @mentions

    clear: ->
      @input.val('')
      @_update()

    destroy: ->
      @input.areacomplete("destroy")
      @input.off(".#{namespace}").attr('name', @hidden.attr('name'))
      @container.replaceWith(@input)


  class MentionsContenteditable extends MentionsBase
    selector: '[data-mention]',

    constructor: (@input, options) ->
      @settings =
        trigger: '@',
        widget: 'editablecomplete',
        markup: @_markupMention,
        preview: true,
        autocomplete: {
          autoFocus: true,
          delay: 0
        }

      super @input, options

      options = $.extend(
        matcher: @_getMatcher(),
        suffix: @marker,
        select: @_onSelect,
        source: @options.source,
        showAtCaret: @options.showAtCaret
      , @options.autocomplete)
      @autocomplete = @input[@options.widget](options)

      @_setValue(@input.html())
      @_initEvents()

    mentionTpl = (mention) ->
      "<strong data-mention=\"#{mention.uid}\">#{mention.value}</strong>"

    insertMention = (mention, pos, suffix) ->
      selection = window.getSelection()
      node = selection.focusNode

      # delete old content and insert mention
      range = selection.getRangeAt 0
      range.setStart node, pos.start
      range.setEnd node, pos.end
      range.deleteContents()

      range.insertNode mention

      if suffix
        suffix = document.createTextNode suffix
        $(suffix).insertAfter mention
        range.setStartAfter suffix
      else
        range.setStartAfter mention

      range.collapse true
      selection.removeAllRanges()
      selection.addRange range
      return mention

    _initEvents: ->
      @input.find(@selector).each (i, el) =>
        @_watch el

    _setValue: (value) ->
      mentionRE = /@\[([^\]]+)\]\(([^ \)]+)\)/g
      value = value.replace mentionRE, (match, value, uid) =>
        mentionTpl(value: value, uid: uid) + @marker
      @input.html value

    _addMention: (data) =>
      mentionNode = $(mentionTpl data)[0]
      mention = insertMention mentionNode, data.pos, @marker
      @_watch mention

    _onSelect: (event, ui) =>
      @_addMention ui.item
      @input.trigger "change.#{namespace}"
      return false

    _watch: (mention) ->
      mention.addEventListener 'DOMCharacterDataModified', (e) ->
        if e.newValue != e.prevValue
          text = e.target
          sel = window.getSelection()
          offset = sel.focusOffset

          $(text).insertBefore mention
          $(mention).remove()

          range = document.createRange()
          range.setStart text, offset
          range.collapse true
          sel.removeAllRanges()
          sel.addRange range

    update: ->
      @_initValue()
      @_initEvents()
      @input.focus()

    setValue: (pieces...) ->
      value = ''
      for piece in pieces
        if typeof piece == 'string'
          value += piece
        else
          value += @options.markup(piece)
      @_setValue(value)
      @_initEvents()
      @input.focus()

    getValue: ->
      value = @input.clone()
      markupMention = @options.markup
      $(@selector, value).replaceWith ->
        uid = $(this).data 'mention'
        name = $(this).text()
        return markupMention({name: name, uid: uid})
      value.html().replace(@marker, '')

    getMentions: ->
      mentions = []
      $(@selector, @input).each ->
        mentions.push
          uid: $(this).data 'mention'
          name: $(this).text()
      return mentions

    clear: ->
      @input.html('')

    destroy: ->
      @input.editablecomplete "destroy"
      @input.off ".#{namespace}"
      @input.html @getValue()


  `
    /*
     Copyright (c) 2009-2011, Kevin Decker <kpdecker@gmail.com>
     */
    function diffChars(oldString, newString) {
      // Handle the identity case (this is due to unrolling editLength == 0
      if (newString === oldString) {
        return [{ value: newString }];
      }
      if (!newString) {
        return [{ value: oldString, removed: true }];
      }
      if (!oldString) {
        return [{ value: newString, added: true }];
      }

      var newLen = newString.length, oldLen = oldString.length;
      var maxEditLength = newLen + oldLen;
      var bestPath = [{ newPos: -1, components: [] }];

      // Seed editLength = 0, i.e. the content starts with the same values
      var oldPos = extractCommon(bestPath[0], newString, oldString, 0);
      if (bestPath[0].newPos+1 >= newLen && oldPos+1 >= oldLen) {
        // Identity per the equality and tokenizer
        return [{value: newString}];
      }

      // Main worker method. checks all permutations of a given edit length for acceptance.
      function execEditLength() {
        for (var diagonalPath = -1*editLength; diagonalPath <= editLength; diagonalPath+=2) {
          var basePath;
          var addPath = bestPath[diagonalPath-1],
            removePath = bestPath[diagonalPath+1];
          oldPos = (removePath ? removePath.newPos : 0) - diagonalPath;
          if (addPath) {
            // No one else is going to attempt to use this value, clear it
            bestPath[diagonalPath-1] = undefined;
          }

          var canAdd = addPath && addPath.newPos+1 < newLen;
          var canRemove = removePath && 0 <= oldPos && oldPos < oldLen;
          if (!canAdd && !canRemove) {
            // If this path is a terminal then prune
            bestPath[diagonalPath] = undefined;
            continue;
          }

          // Select the diagonal that we want to branch from. We select the prior
          // path whose position in the new string is the farthest from the origin
          // and does not pass the bounds of the diff graph
          if (!canAdd || (canRemove && addPath.newPos < removePath.newPos)) {
            basePath = clonePath(removePath);
            pushComponent(basePath.components, undefined, true);
          } else {
            basePath = addPath;   // No need to clone, we've pulled it from the list
            basePath.newPos++;
            pushComponent(basePath.components, true, undefined);
          }

          var oldPos = extractCommon(basePath, newString, oldString, diagonalPath);

          // If we have hit the end of both strings, then we are done
          if (basePath.newPos+1 >= newLen && oldPos+1 >= oldLen) {
            return buildValues(basePath.components, newString, oldString);
          } else {
            // Otherwise track this path as a potential candidate and continue.
            bestPath[diagonalPath] = basePath;
          }
        }

        editLength++;
      }

      // Performs the length of edit iteration. Is a bit fugly as this has to support the
      // sync and async mode which is never fun. Loops over execEditLength until a value
      // is produced.
      var editLength = 1;
      while(editLength <= maxEditLength) {
        var ret = execEditLength();
        if (ret) {
          return ret;
        }
      }
    }

    function buildValues(components, newString, oldString) {
      var componentPos = 0,
        componentLen = components.length,
        newPos = 0,
        oldPos = 0;

      for (; componentPos < componentLen; componentPos++) {
        var component = components[componentPos];
        if (!component.removed) {
          component.value = newString.slice(newPos, newPos + component.count);
          newPos += component.count;

          // Common case
          if (!component.added) {
            oldPos += component.count;
          }
        } else {
          component.value = oldString.slice(oldPos, oldPos + component.count);
          oldPos += component.count;
        }
      }

      return components;
    }

    function pushComponent(components, added, removed) {
      var last = components[components.length-1];
      if (last && last.added === added && last.removed === removed) {
        // We need to clone here as the component clone operation is just
        // as shallow array clone
        components[components.length-1] = {count: last.count + 1, added: added, removed: removed };
      } else {
        components.push({count: 1, added: added, removed: removed });
      }
    }

    function extractCommon(basePath, newString, oldString, diagonalPath) {
      var newLen = newString.length,
        oldLen = oldString.length,
        newPos = basePath.newPos,
        oldPos = newPos - diagonalPath,

        commonCount = 0;
      while (newPos+1 < newLen && oldPos+1 < oldLen && newString[newPos+1] == oldString[oldPos+1]) {
        newPos++;
        oldPos++;
        commonCount++;
      }

      if (commonCount) {
        basePath.components.push({count: commonCount});
      }

      basePath.newPos = newPos;
      return oldPos;
    }

    function clonePath(path) {
      return { newPos: path.newPos, components: path.components.slice(0) };
    }`


  $.fn[namespace] = (options, args...) ->
    returnValue = this

    this.each(->
      if typeof options == 'string' and options.charAt(0) != '_'
        instance = $(this).data('mentionsInput')
        if options of instance
          returnValue = instance[options](args...)
      else
        if this.tagName in ['INPUT', 'TEXTAREA']
          $(this).data 'mentionsInput', new MentionsInput($(this), options)
        else if this.contentEditable == "true"
          $(this).data 'mentionsInput', new MentionsContenteditable($(this), options)
    )
    return returnValue

) jQuery