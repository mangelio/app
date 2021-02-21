import { iriToId } from './api'

const issueTransformer = {
  isOverdue: function (issue) {
    if (!issue.deadline || issue.resolvedBy || issue.closedBy) {
      return false
    }

    const deadline = Date.parse(issue.deadline)
    const now = Date.now()
    return deadline < now
  }
}

const treeTransformer = {
  /*
   the data structure inside this transformer is a "tree".
   a "tree" is a list of entries; each entry looks like {entity, children:[tree, tree, ...]}
   */
  _createParentLookup: function (defaultParent, entities, parentResolveFunc) {
    const parentLookup = {}

    entities.forEach(entity => {
      const parentKey = parentResolveFunc(entity) ?? defaultParent

      if (!parentLookup[parentKey]) {
        parentLookup[parentKey] = []
      }

      parentLookup[parentKey].push(entity)
    })

    return parentLookup
  },
  _create: function (key, parentLookup) {
    if (!(key in parentLookup)) {
      return []
    }

    const children = parentLookup[key].map(entry => ({
      entity: entry,
      children: this._create(entry['@id'], parentLookup)
    }))

    // remove processed entries in lookup
    delete parentLookup[key]

    return children
  },
  _createWithReminders: function (rootKey, parentLookup) {
    const tree = this._create(rootKey, parentLookup)

    // append any entries that remain. this case should never happen (mean broken relations!)
    for (const key in parentLookup) {
      if (Object.prototype.hasOwnProperty.call(parentLookup, key)) {
        tree.push(...parentLookup[key].map(entry => ({
          entity: entry,
          children: []
        })))
      }
    }

    return tree
  },
  _sortInPlace: function (tree, sortFunc) {
    tree.sort((a, b) => sortFunc(a, b))
    tree.forEach(child => {
      this._sortInPlace(child.children, sortFunc)
    })
  },
  _addLevelInPlace: function (tree, level = 0) {
    tree.forEach(child => {
      child.level = level
      this._addLevelInPlace(child.children, level + 1)
    })
  },
  _addParentInPlace: function (tree, parent = null) {
    tree.forEach(child => {
      child.parent = parent
      this._addParentInPlace(child.children, child)
    })
  },
  _addParentsInPlace: function (tree, parents = []) {
    tree.forEach(child => {
      child.parents = parents
      const newParents = [...parents, child]
      this._addParentsInPlace(child.children, newParents)
    })
  },
  _traverseDepthFirst: function (tree, func) {
    tree.forEach(child => {
      this._traverseDepthFirst(child.children, func)
      func(child)
    })
  },
  _addPropertyInPlace: function (tree, property, propertyResolveFunc) {
    this._traverseDepthFirst(tree, child => { child[property] = propertyResolveFunc(child) })
  },
  _flattenToList: function (tree) {
    let result = []
    tree.forEach(child => {
      result.push(child)
      result = result.concat(...this._flattenToList(child.children))
    })

    return result
  },
  _flattenToLookup: function (tree, keyFunc) {
    const lookup = {}
    tree.forEach(child => {
      const key = keyFunc(child)
      lookup[key] = child

      const recursiveLookup = this._flattenToLookup(child.children, keyFunc)
      Object.assign(lookup, recursiveLookup)
    })

    return lookup
  }
}

const mapTransformer = {
  PROPERTY_LEVEL: 1,
  PROPERTY_PARENT: 2,
  PROPERTY_PARENTS: 4,
  PROPERTY_MAP_PARENT_NAMES: 8,
  PROPERTY_HAS_CHILD_WITH_ISSUES: 16,
  _createMapTree: function (maps) {
    const noParentKey = 'root'
    const parentLookup = treeTransformer._createParentLookup(noParentKey, maps, m => m.parent)
    return treeTransformer._createWithReminders(noParentKey, parentLookup)
  },
  _createSortedMapTree: function (maps) {
    const tree = this._createMapTree(maps)

    treeTransformer._sortInPlace(tree, (a, b) => a.entity.name.localeCompare(b.entity.name))

    return tree
  },
  _addPropertiesInPlace: function (tree, properties = 0) {
    // noinspection JSBitwiseOperatorUsage
    if (properties & this.PROPERTY_LEVEL) {
      treeTransformer._addLevelInPlace(tree)
    }
    if (properties & this.PROPERTY_PARENT) {
      treeTransformer._addParentInPlace(tree)
    }
    if (properties & this.PROPERTY_PARENTS) {
      treeTransformer._addParentsInPlace(tree)
    }
    if (properties & this.PROPERTY_MAP_PARENT_NAMES) {
      if (!(properties & this.PROPERTY_PARENTS)) {
        treeTransformer._addParentsInPlace(tree)
      }
      treeTransformer._addPropertyInPlace(tree, 'mapParentNames', node => node.parents.map(p => p.entity.name))
    }
    if (properties & this.PROPERTY_HAS_CHILD_WITH_ISSUES) {
      treeTransformer._addPropertyInPlace(tree, 'hasChildWithIssues', child => child.children.some(c => c.issueCount > 0 || c.hasChildWithIssues))
    }
  },
  _flattenToListWithProperties: function (tree, properties = 0) {
    this._addPropertiesInPlace(tree, properties)

    return treeTransformer._flattenToList(tree)
  },
  _flattenToLookupWithProperties: function (tree, properties = 0) {
    this._addPropertiesInPlace(tree, properties)

    return treeTransformer._flattenToLookup(tree, node => node.entity['@id'])
  },
  _addIssueGroupsInPlace: function (tree, mapGroups) {
    const mapGroupLookup = {}
    mapGroups.forEach(mg => { mapGroupLookup[mg.entity] = mg })

    treeTransformer._traverseDepthFirst(tree, node => {
      const mapGroup = mapGroupLookup[node.entity['@id']]
      node.issueCount = mapGroup ? mapGroup.count : 0
      node.maxDeadline = mapGroup && mapGroup.maxDeadline ? mapGroup.maxDeadline : null
    })
  },
  orderedList: function (maps, properties = 0) {
    const tree = this._createSortedMapTree(maps)

    return this._flattenToListWithProperties(tree, properties)
  },
  orderedListWithIssuesGroups: function (maps, issueGroupsByMap, properties = 0) {
    const tree = this._createSortedMapTree(maps)

    this._addIssueGroupsInPlace(tree, issueGroupsByMap)

    return this._flattenToListWithProperties(tree, properties)
  },
  lookup: function (maps, properties) {
    const tree = this._createMapTree(maps)

    return this._flattenToLookupWithProperties(tree, properties)
  },
  groupByIssueCount: function (maps, mapGroups, maxCount) {
    const tree = this._createMapTree(maps)

    this._addIssueGroupsInPlace(tree, mapGroups)

    const list = treeTransformer._flattenToList(tree)
    list.forEach(entry => { entry.taken = false })
    list.sort((a, b) => a.issueCount - b.issueCount)

    console.log(list)

    // get issue count by map (DONE; in mapGroups)
    // order maps by issue count (biggest first)
    // take highest a
    // join a with all siblings (and all its children) + a.parent into b
    // if b > limit then take a else add b to list
    // repeat while list not empty / count of next item > 0

    // return list of list of mapIds (already as Ids, not IRI)

    return [list]
  }
}

const filterTransformer = {
  defaultFilter: function (view) {
    return {
      isDeleted: false,
      state: view === 'foyer' ? 1 : 14 // 14 = 8 | 4 | 2
    }
  },
  defaultConfiguration: function (view) {
    return {
      showState: view === 'register',
      state: false,
      craftsmen: false,
      maps: false,
      deadline: false,
      time: false
    }
  },
  shouldIncludeCollection: function (value, collection) {
    return value && (value.length > 0 || value.length !== collection.length)
  },
  filterToQuery: function (defaultFilter, filter, configuration, craftsmen, maps) {
    const query = Object.assign({}, defaultFilter)

    if (!filter) {
      return query
    }

    const textProps = ['number', 'description']
    textProps.filter(p => filter[p])
      .forEach(p => { query[p] = filter[p] })

    const booleanProps = ['isMarked', 'wasAddedWithClient']
    booleanProps.filter(p => filter[p] || filter[p] === false)
      .forEach(p => { query[p] = filter[p] })

    if (!configuration) {
      return query
    }

    if (configuration.state) {
      query.state = filter.state
    }
    if (configuration.craftsmen && this.shouldIncludeCollection(filter.craftsmen, craftsmen)) {
      query['craftsman[]'] = filter.craftsmen.map(e => iriToId(e['@id']))
    }
    if (configuration.maps && this.shouldIncludeCollection(filter.maps, maps)) {
      query['map[]'] = filter.maps.map(e => iriToId(e['@id']))
    }

    const whitelistDateTimePropNames = []
    if (configuration.deadline) {
      whitelistDateTimePropNames.push('deadline')
    }
    if (configuration.time) {
      whitelistDateTimePropNames.push('createdAt', 'registeredAt', 'resolvedAt', 'closedAt')
    }
    const whitelistDateTimeProps = []
    whitelistDateTimePropNames.forEach(prop => {
      whitelistDateTimeProps.push(prop + '[before]')
      whitelistDateTimeProps.push(prop + '[after]')
    })
    whitelistDateTimeProps.filter(p => filter[p])
      .forEach(p => { query[p] = filter[p] })

    return query
  }
}

export { issueTransformer, mapTransformer, filterTransformer }
